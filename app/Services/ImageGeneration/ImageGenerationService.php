<?php

namespace App\Services\ImageGeneration;

use App\Exceptions\ContentPolicyException;
use App\Mail\ProviderFailedAdminMail;
use App\Models\ProviderLog;
use App\Services\ImageGeneration\Contracts\ImageProviderInterface;
use App\Services\ImageGeneration\Providers\EvolinkProvider;
use App\Services\ImageGeneration\Providers\FalAiProvider;
use App\Services\ImageGeneration\Providers\NovitaProvider;
use App\Services\ImageGeneration\Providers\OpenRouterProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ImageGenerationService
{
    private array $providerChain = [];

    private static array $providerMap = [
        'fal_ai'     => FalAiProvider::class,
        'openrouter' => OpenRouterProvider::class,
        'evolink'    => EvolinkProvider::class,
        'novita'     => NovitaProvider::class,
    ];

    public function __construct()
    {
        $chain = config('image_providers.chain', []);

        foreach ($chain as $name) {
            if (isset(self::$providerMap[$name])) {
                $provider = app(self::$providerMap[$name]);
                if ($provider->isAvailable()) {
                    $this->providerChain[] = $provider;
                }
            }
        }
    }

    /**
     * Generar imagen iterando la cadena de proveedores con fallback.
     * Si se supera el presupuesto diario, prioriza el proveedor más barato.
     */
    public function generate(array $params): array
    {
        $chain = $this->getOptimizedChain();
        $lastException = null;

        foreach ($chain as $provider) {
            if ($this->isInCooldown($provider)) {
                Log::info("Provider {$provider->getName()} en cooldown, saltando");
                continue;
            }

            try {
                $result = $provider->generate($params);

                Log::info("Generación exitosa con {$provider->getName()}", [
                    'model'         => $result['model'] ?? 'unknown',
                    'elapsed_ms'    => $result['elapsed_ms'] ?? 0,
                    'cost_estimate' => $result['cost_estimate'] ?? 0,
                ]);

                return $result;

            } catch (ContentPolicyException $e) {
                // Contenido prohibido — NO intentar siguiente proveedor, re-throw directo
                Log::warning("Provider {$provider->getName()} rechazo por content policy", [
                    'error' => $e->getMessage(),
                ]);

                try {
                    ProviderLog::create([
                        'provider'      => $provider->getName(),
                        'model'         => config("image_providers.providers.{$provider->getName()}.model"),
                        'status'        => 'error',
                        'error_message' => substr($e->getMessage(), 0, 500),
                    ]);
                } catch (\Throwable $logErr) {
                    // No bloquear por fallo de log
                }

                throw $e; // Rompe la cadena — GeneratePortraitJob se encarga del ban

            } catch (\Exception $e) {
                $isTimeout = str_contains($e->getMessage(), 'timeout')
                          || str_contains($e->getMessage(), 'timed out');

                $cooldownSeconds = $isTimeout
                    ? config('image_providers.cooldown_timeout', 300)
                    : config('image_providers.cooldown_error', 120);

                $this->setCooldown($provider, $cooldownSeconds);

                Log::warning("Provider {$provider->getName()} falló", [
                    'error'    => $e->getMessage(),
                    'timeout'  => $isTimeout,
                    'cooldown' => $cooldownSeconds,
                ]);

                // Registrar error en provider_logs para estadísticas
                try {
                    ProviderLog::create([
                        'provider'      => $provider->getName(),
                        'model'         => config("image_providers.providers.{$provider->getName()}.model"),
                        'status'        => $isTimeout ? 'timeout' : 'error',
                        'error_message' => substr($e->getMessage(), 0, 500),
                    ]);
                } catch (\Throwable $logErr) {
                    // No bloquear por fallo de log
                }

                $lastException = $e;

                // Notificar al admin por email (máx 1 por proveedor cada 15 min para no saturar)
                $this->notifyAdminProviderFailed(
                    $provider->getName(),
                    $e->getMessage(),
                    $isTimeout,
                    $cooldownSeconds,
                    allFailed: false,
                );
            }
        }

        // CRÍTICO: todos fallaron — notificar con urgencia
        $this->notifyAdminProviderFailed(
            'TODOS',
            $lastException?->getMessage() ?? 'ninguno',
            false,
            0,
            allFailed: true,
        );

        throw new \RuntimeException(
            'Todos los proveedores IA fallaron. Último error: ' . ($lastException?->getMessage() ?? 'ninguno')
        );
    }

    /**
     * Si se supera el presupuesto diario, reordena la cadena poniendo
     * el proveedor más barato primero.
     */
    private function getOptimizedChain(): array
    {
        $chain = $this->providerChain;
        $dailyBudget = config('image_providers.daily_budget_usd', 50);
        $todayCost = $this->getTodayCost();

        if ($todayCost >= $dailyBudget && count($chain) > 1) {
            Log::warning("Presupuesto IA diario superado ({$todayCost}/{$dailyBudget} USD). Priorizando proveedor barato.");

            usort($chain, fn ($a, $b) =>
                $a->estimateCost([]) <=> $b->estimateCost([])
            );
        }

        return $chain;
    }

    /**
     * Coste IA acumulado hoy en USD.
     */
    public function getTodayCost(): float
    {
        return Cache::remember('ia_cost_today_' . date('Y-m-d'), 300, function () {
            return (float) ProviderLog::where('status', 'success')
                ->whereDate('created_at', today())
                ->sum('cost_usd');
        });
    }

    private function isInCooldown(ImageProviderInterface $provider): bool
    {
        return Cache::has("provider_cooldown_{$provider->getName()}");
    }

    private function setCooldown(ImageProviderInterface $provider, int $seconds): void
    {
        Cache::put("provider_cooldown_{$provider->getName()}", true, $seconds);
    }

    /**
     * Notifica por email a los admins cuando un proveedor IA falla.
     * Rate-limited: máximo 1 email por proveedor cada 15 minutos.
     */
    private function notifyAdminProviderFailed(
        string $providerName,
        string $errorMessage,
        bool $isTimeout,
        int $cooldownSeconds,
        bool $allFailed = false,
    ): void {
        try {
            $cacheKey = "admin_notified_provider_fail_{$providerName}";

            // No saturar: máx 1 email cada 15 min por proveedor (críticos siempre)
            if (!$allFailed && Cache::has($cacheKey)) {
                return;
            }

            $adminEmails = config('genpic.admin_emails', []);
            if (empty($adminEmails)) {
                return;
            }

            $mail = new ProviderFailedAdminMail($providerName, $errorMessage, $isTimeout, $cooldownSeconds, $allFailed);

            foreach ($adminEmails as $email) {
                Mail::to($email)->send($mail);
            }

            if (!$allFailed) {
                Cache::put($cacheKey, true, 900); // 15 min
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar email admin de fallo de proveedor', ['error' => $e->getMessage()]);
        }
    }
}
