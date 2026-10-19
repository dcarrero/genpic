<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?string $navigationLabel = 'Diagnóstico';
    protected static ?string $title = 'Diagnóstico del Sistema';
    protected static string $view = 'filament.pages.system-health';
    protected static ?int $navigationSort = 97;

    public array $results = [];
    public bool $tested = false;

    public function runAllTests(): void
    {
        $this->results = [];
        $this->results['db'] = $this->testDatabase();
        $this->results['redis'] = $this->testRedis();
        $this->results['wasabi'] = $this->testWasabi();
        $this->results['fal_ai'] = $this->testFalAi();
        $this->results['openrouter'] = $this->testOpenRouter();
        $this->results['novita'] = $this->testNovita();
        $this->results['evolink'] = $this->testEvolink();
        $this->results['stripe'] = $this->testStripe();
        $this->results['mail'] = $this->testMail();
        $this->results['huggingface_nsfw'] = $this->testHuggingFaceNsfw();
        $this->tested = true;

        $errors = collect($this->results)->where('status', 'error')->count();
        $warnings = collect($this->results)->where('status', 'warning')->count();

        if ($errors === 0 && $warnings === 0) {
            Notification::make()->title('Todo OK')->success()->send();
        } elseif ($errors > 0) {
            Notification::make()->title("{$errors} errores encontrados")->danger()->send();
        } else {
            Notification::make()->title("{$warnings} avisos")->warning()->send();
        }
    }

    public function testSingleService(string $service): void
    {
        $method = 'test' . Str::studly($service);
        if (method_exists($this, $method)) {
            $this->results[$service] = $this->$method();
            $this->tested = true;
        }
    }

    private function testDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $tables = DB::select('SHOW TABLES');
            $ms = round((microtime(true) - $start) * 1000);
            $styles = DB::table('artistic_styles')->count();
            $products = DB::table('products')->count();
            $gallery = DB::table('gallery_examples')->count();
            $users = DB::table('users')->count();

            return [
                'name' => 'Base de datos',
                'status' => 'ok',
                'message' => count($tables) . " tablas · {$styles} estilos · {$products} productos · {$gallery} galería · {$users} usuarios",
                'ms' => $ms,
            ];
        } catch (\Exception $e) {
            return ['name' => 'Base de datos', 'status' => 'error', 'message' => $e->getMessage(), 'ms' => 0];
        }
    }

    private function testRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $ms = round((microtime(true) - $start) * 1000);
            return ['name' => 'Redis', 'status' => 'ok', 'message' => "Conectado ({$ms}ms)", 'ms' => $ms];
        } catch (\Exception $e) {
            return ['name' => 'Redis', 'status' => 'error', 'message' => $e->getMessage(), 'ms' => 0];
        }
    }

    private function testWasabi(): array
    {
        $key = config('filesystems.disks.wasabi.key');
        if (empty($key)) {
            return ['name' => 'Wasabi S3', 'status' => 'warning', 'message' => 'Sin API key configurada', 'ms' => 0];
        }

        try {
            $disk = Storage::disk('wasabi');
            $testFile = '_health_check_' . Str::random(8) . '.txt';
            $start = microtime(true);

            // Escribir
            $disk->put($testFile, 'health-check-' . now()->toIso8601String());

            // Leer
            $content = $disk->get($testFile);

            // Borrar
            $disk->delete($testFile);

            $ms = round((microtime(true) - $start) * 1000);

            if (str_starts_with($content, 'health-check-')) {
                return ['name' => 'Wasabi S3', 'status' => 'ok', 'message' => "Escritura + lectura + borrado OK ({$ms}ms)", 'ms' => $ms];
            }
            return ['name' => 'Wasabi S3', 'status' => 'error', 'message' => 'Lectura no coincide', 'ms' => $ms];
        } catch (\Exception $e) {
            return ['name' => 'Wasabi S3', 'status' => 'error', 'message' => $e->getMessage(), 'ms' => 0];
        }
    }

    private function testFalAi(): array
    {
        if (!config('image_providers.providers.fal_ai.enabled', false)) {
            return ['name' => 'fal.ai', 'status' => 'skip', 'message' => 'Desactivado', 'ms' => 0];
        }

        $key = config('image_providers.providers.fal_ai.key');
        if (empty($key)) {
            return ['name' => 'fal.ai', 'status' => 'warning', 'message' => 'Sin API key', 'ms' => 0];
        }

        try {
            $start = microtime(true);
            // Solo verificar autenticación sin consumir tokens
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $key,
            ])->timeout(10)->get('https://rest.alpha.fal.ai/');
            $ms = round((microtime(true) - $start) * 1000);

            // Cualquier respuesta que no sea 401/403 indica key válida
            if ($response->status() !== 401 && $response->status() !== 403) {
                return ['name' => 'fal.ai', 'status' => 'ok', 'message' => "API key válida ({$ms}ms)", 'ms' => $ms];
            }
            return ['name' => 'fal.ai', 'status' => 'error', 'message' => 'API key inválida (HTTP ' . $response->status() . ')', 'ms' => $ms];
        } catch (\Exception $e) {
            return ['name' => 'fal.ai', 'status' => 'error', 'message' => $e->getMessage(), 'ms' => 0];
        }
    }

    private function testOpenRouter(): array
    {
        if (!config('image_providers.providers.openrouter.enabled', false)) {
            return ['name' => 'OpenRouter', 'status' => 'skip', 'message' => 'Desactivado', 'ms' => 0];
        }

        $key = config('image_providers.providers.openrouter.key');
        if (empty($key)) {
            return ['name' => 'OpenRouter', 'status' => 'warning', 'message' => 'Sin API key', 'ms' => 0];
        }

        try {
            $start = microtime(true);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $key,
            ])->timeout(10)->get('https://openrouter.ai/api/v1/auth/key');
            $ms = round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $data = $response->json('data', []);
                $credit = $data['limit_remaining'] ?? 'unknown';
                return ['name' => 'OpenRouter', 'status' => 'ok', 'message' => "Key válida, crédito: {$credit} ({$ms}ms)", 'ms' => $ms];
            }
            return ['name' => 'OpenRouter', 'status' => 'error', 'message' => 'HTTP ' . $response->status(), 'ms' => $ms];
        } catch (\Exception $e) {
            return ['name' => 'OpenRouter', 'status' => 'error', 'message' => $e->getMessage(), 'ms' => 0];
        }
    }

    private function testNovita(): array
    {
        if (!config('image_providers.providers.novita.enabled', false)) {
            return ['name' => 'Novita', 'status' => 'skip', 'message' => 'Desactivado', 'ms' => 0];
        }

        $key = config('image_providers.providers.novita.key');
        if (empty($key)) {
            return ['name' => 'Novita', 'status' => 'warning', 'message' => 'Sin API key', 'ms' => 0];
        }

        try {
            $start = microtime(true);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $key,
            ])->timeout(10)->get('https://api.novita.ai/v3/user');
            $ms = round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $balance = $response->json('credit_balance', 'unknown');
                return ['name' => 'Novita', 'status' => 'ok', 'message' => "Key válida, balance: {$balance} ({$ms}ms)", 'ms' => $ms];
            }
            return ['name' => 'Novita', 'status' => 'error', 'message' => 'HTTP ' . $response->status(), 'ms' => $ms];
        } catch (\Exception $e) {
            return ['name' => 'Novita', 'status' => 'error', 'message' => $e->getMessage(), 'ms' => 0];
        }
    }

    private function testEvolink(): array
    {
        if (!config('image_providers.providers.evolink.enabled', false)) {
            return ['name' => 'Evolink', 'status' => 'skip', 'message' => 'Desactivado', 'ms' => 0];
        }

        $key = config('image_providers.providers.evolink.key');
        if (empty($key)) {
            return ['name' => 'Evolink', 'status' => 'warning', 'message' => 'Sin API key', 'ms' => 0];
        }

        return ['name' => 'Evolink', 'status' => 'ok', 'message' => 'Key configurada', 'ms' => 0];
    }

    private function testStripe(): array
    {
        $key = config('services.stripe.secret');
        if (empty($key)) {
            return ['name' => 'Stripe', 'status' => 'warning', 'message' => 'Sin API key — pagos no funcionarán', 'ms' => 0];
        }

        try {
            $start = microtime(true);
            $stripe = new \Stripe\StripeClient($key);
            $balance = $stripe->balance->retrieve();
            $ms = round((microtime(true) - $start) * 1000);

            $mode = str_starts_with($key, 'sk_live_') ? 'LIVE' : 'TEST';
            return ['name' => 'Stripe', 'status' => 'ok', 'message' => "Conectado modo {$mode} ({$ms}ms)", 'ms' => $ms];
        } catch (\Exception $e) {
            return ['name' => 'Stripe', 'status' => 'error', 'message' => $e->getMessage(), 'ms' => 0];
        }
    }

    private function testHuggingFaceNsfw(): array
    {
        if (!config('genpic.content_moderation.enabled')) {
            return ['name' => 'HuggingFace NSFW', 'status' => 'skip', 'message' => 'Moderacion desactivada', 'ms' => 0];
        }

        $token = config('services.huggingface.token');
        if (empty($token)) {
            return ['name' => 'HuggingFace NSFW', 'status' => 'warning', 'message' => 'Sin token — moderacion no funcionara', 'ms' => 0];
        }

        try {
            $start = microtime(true);
            // Enviar imagen minima (1x1 pixel JPEG) para verificar conectividad
            $img = imagecreatetruecolor(1, 1);
            ob_start();
            imagejpeg($img, null, 50);
            $tiny = ob_get_clean();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])
            ->timeout(15)
            ->withBody($tiny, 'application/octet-stream')
            ->post('https://router.huggingface.co/hf-inference/models/Falconsai/nsfw_image_detection');
            $ms = round((microtime(true) - $start) * 1000);

            if ($response->successful() && is_array($response->json())) {
                return ['name' => 'HuggingFace NSFW', 'status' => 'ok', 'message' => "API conectada ({$ms}ms)", 'ms' => $ms];
            }

            if ($response->status() === 503) {
                return ['name' => 'HuggingFace NSFW', 'status' => 'warning', 'message' => "Modelo cargando ({$ms}ms) — reintenta en 20s", 'ms' => $ms];
            }

            return ['name' => 'HuggingFace NSFW', 'status' => 'error', 'message' => 'HTTP ' . $response->status() . ' — ' . substr($response->body(), 0, 200), 'ms' => $ms];
        } catch (\Exception $e) {
            return ['name' => 'HuggingFace NSFW', 'status' => 'error', 'message' => $e->getMessage(), 'ms' => 0];
        }
    }

    private function testMail(): array
    {
        $mailer = config('mail.default');
        $host = config("mail.mailers.{$mailer}.host", 'no configurado');

        if ($mailer === 'log') {
            return ['name' => 'Email', 'status' => 'warning', 'message' => 'Usando driver "log" — los emails no se envían', 'ms' => 0];
        }

        return ['name' => 'Email', 'status' => 'ok', 'message' => "Driver: {$mailer}, Host: {$host}", 'ms' => 0];
    }
}
