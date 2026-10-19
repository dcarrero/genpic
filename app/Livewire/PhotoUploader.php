<?php

namespace App\Livewire;

use App\Jobs\GeneratePortraitJob;
use App\Mail\ContentViolationAdminMail;
use App\Mail\SessionCreatedMail;
use App\Models\ArtisticStyle;
use App\Models\ContentViolation;
use App\Models\GenerationSession;
use App\Models\User;
use App\Services\ContentModeration\ContentModerationService;
use App\Services\ContentModeration\IpBanService;
use App\Services\StorageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithFileUploads;

class PhotoUploader extends Component
{
    use WithFileUploads;

    public string $step = 'upload'; // upload | processing | email_sent | pin_verify
    public ?string $styleKey = null;
    public ?string $sessionUuid = null;
    public ?string $error = null;
    /** Feedback de resendPin(): sin esto el botón no daba señal alguna. */
    public bool $pinResent = false;
    public bool $photoReady = false;

    /** Mostrar campo email en el formulario de upload */
    public bool $showEmail;
    /** Email es obligatorio (PIN activo) */
    public bool $emailRequired;
    /** PIN de verificación activo */
    public bool $requirePin;

    public $photo;
    public string $email = '';
    public string $orientation = 'auto';
    public bool $faceDetected = false;
    public int $faceCount = 0;
    public bool $faceDetectionEnabled;

    public ?string $generationStatus = null;
    /** Indica si ya se capturó email (para no volver a pedirlo en polling) */
    public bool $emailCaptured = false;
    /** Máximo de caras permitidas para el estilo actual (0 = sin límite) */
    public int $maxFaces = 0;
    /** Categoría del estilo actual (para mostrar tips de foto) */
    public string $styleCategory = 'artistic';

    protected $listeners = ['style-selected' => 'setStyle'];

    public function mount(?string $style = null)
    {
        $this->styleKey = $style ?? 'renacimiento';
        $this->faceDetectionEnabled = (bool) config('genpic.face_detection_enabled', true);

        $emailEnabled = (bool) config('genpic.require_email_before_generation', false);
        $pinEnabled = (bool) config('genpic.require_pin', false);

        // PIN sin email no tiene sentido — ignorar PIN
        $this->requirePin = $emailEnabled && $pinEnabled;
        $this->emailRequired = $this->requirePin; // obligatorio solo si PIN
        $this->showEmail = $emailEnabled;          // mostrar campo si EMAIL=true

        $this->loadMaxFaces();
    }

    public function setStyle(string $styleKey)
    {
        $this->styleKey = $styleKey;
        $this->loadMaxFaces();
    }

    private function loadMaxFaces(): void
    {
        $style = ArtisticStyle::where('style_key', $this->styleKey)->first();
        $this->maxFaces = $style?->max_faces ?? 0;
        $this->styleCategory = $style?->style_category ?? 'artistic';
    }

    public function updatedPhoto()
    {
        $this->validateOnly('photo', [
            'photo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);
        $this->error = null;
        $this->photoReady = true;
        // No resetear faceDetected/faceCount aquí — JS (face-api.js) es la
        // única autoridad y ya habrá seteado el valor correcto via @this.set()
        // antes de que el upload de Livewire complete.
    }

    public function submit()
    {
        // Face detection (validado client-side con face-api.js)
        if ($this->faceDetectionEnabled && !$this->faceDetected) {
            $this->error = __('ui.no_face_detected');
            return;
        }

        // Max faces — estilos profesionales/temáticos requieren 1 sola cara
        if ($this->maxFaces > 0 && $this->faceCount > $this->maxFaces) {
            $this->error = __('ui.max_faces_error', ['max' => $this->maxFaces]);
            return;
        }

        // Validación: email obligatorio solo si PIN activo
        $rules = [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ];
        if ($this->emailRequired) {
            $rules['email'] = ['required', 'email:rfc', 'max:255'];
        } elseif ($this->email) {
            // Si dio email opcional, validar formato
            $rules['email'] = ['email:rfc', 'max:255'];
        }
        $this->validate($rules);

        // Rate limit
        $key = 'generation:' . request()->ip();
        $max = config('genpic.rate_limit.generations_per_ip', 3);
        $decay = config('genpic.rate_limit.period_hours', 24) * 3600;

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $this->error = __('ui.rate_limit');
            return;
        }

        try {
            $style = ArtisticStyle::where('style_key', $this->styleKey)->firstOrFail();
            $storage = app(StorageService::class);

            // 1. Guardar foto (con auto-orientación EXIF para corregir fotos de móvil rotadas)
            $rawContents = file_get_contents($this->photo->getRealPath());
            $manager = new \Intervention\Image\ImageManager(\Intervention\Image\Drivers\Imagick\Driver::class);
            $orientedImage = $manager->read($rawContents)->orient();
            $ext = strtolower($this->photo->getClientOriginalExtension());
            $photoContents = ($ext === 'png')
                ? $orientedImage->toPng()->toString()
                : $orientedImage->toJpeg(92)->toString();

            // Content moderation — CAPA 1: detectar contenido prohibido ANTES de almacenar/enviar a IA
            $moderationService = app(ContentModerationService::class);
            if ($moderationService->isEnabled()) {
                $moderationResult = $moderationService->analyze($photoContents);

                if ($moderationResult->isProhibited) {
                    $fileHash = hash('sha256', $photoContents);

                    $violation = ContentViolation::record(
                        ip: request()->ip(),
                        email: $this->email ?: null,
                        userAgent: request()->userAgent(),
                        fileHash: $fileHash,
                        fileName: $this->photo?->getClientOriginalName(),
                        fileSizeBytes: $this->photo?->getSize(),
                        violationType: $moderationResult->violationType,
                        violationScore: $moderationResult->score,
                        provider: $moderationResult->provider,
                        providerResponse: $moderationResult->rawResponse,
                    );

                    $banHours = config('genpic.content_moderation.ban_duration_hours', 72);
                    app(IpBanService::class)->ban(request()->ip(), $banHours, $violation->id);

                    $this->notifyAdminContentViolation($violation, $banHours);

                    // Limpiar foto — nunca almacenar contenido prohibido
                    $this->photo = null;
                    $this->photoReady = false;

                    // Mensaje generico — NO revelar deteccion NSFW ni ban
                    $this->error = __('ui.content_not_permitted');
                    return;
                }
            }

            $path = $storage->store(
                $photoContents,
                'originals',
                now()->format('Y/m/d'),
                $ext ?: 'jpg'
            );

            // Detectar orientación
            $resolvedOrientation = $this->orientation;
            if ($resolvedOrientation === 'auto') {
                $imageSize = getimagesizefromstring($photoContents);
                $resolvedOrientation = ($imageSize && $imageSize[0] >= $imageSize[1]) ? 'horizontal' : 'vertical';
            }

            // 2. Crear usuario si email proporcionado
            $user = null;
            if ($this->email) {
                $user = User::firstOrCreate(
                    ['email' => $this->email],
                    [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'locale'     => app()->getLocale(),
                    ]
                );
                $user->update(['last_seen_at' => now()]);
                $this->emailCaptured = true;
            }

            // 3. Crear sesión (ab_variant guarda la orientación elegida)
            $session = GenerationSession::create([
                'user_id'             => $user?->id,
                'style_key'           => $style->style_key,
                'artistic_style_id'   => $style->id,
                'original_photo_path' => $path,
                'original_filename'   => $this->photo->getClientOriginalName(),
                'original_size_bytes' => $this->photo->getSize(),
                'ip_address'          => request()->ip(),
                'locale'              => app()->getLocale(),
                'status'              => 'pending',
                'face_detected'       => $this->faceDetected,
                'email_captured_at'   => $user ? now() : null,
                'ab_variant'          => $resolvedOrientation, // orientación: vertical/horizontal/square
            ]);

            RateLimiter::hit($key, $decay);
            $this->sessionUuid = $session->uuid;

            // Limpiar foto temporal para evitar errores en re-renders
            $this->photo = null;
            $this->photoReady = false;

            if ($this->requirePin) {
                // Flujo PIN: email obligatorio, enviar PIN, esperar verificación antes de generar
                $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                \Illuminate\Support\Facades\Redis::setex("pin:{$session->uuid}", 3600, "{$pin}:" . strtolower($this->email));
                \Illuminate\Support\Facades\Redis::del("pin_attempts:{$session->uuid}");

                try {
                    Mail::raw(
                        $this->buildPinEmailBody($pin),
                        function ($message) {
                            $message->to($this->email)
                                ->subject('GenPic — ' . __('ui.pin_subject'));
                        }
                    );
                    $session->update(['session_email_sent_at' => now()]);
                } catch (\Exception $e) {
                    Log::warning('PIN email send failed', ['error' => $e->getMessage()]);
                }
                $this->step = 'pin_verify';
                $this->dispatch('generation-started');
            } else {
                // Flujo sin PIN: genera DIRECTO, muestra procesando
                GeneratePortraitJob::dispatch($session);
                $session->update(['status' => 'processing']);
                $this->step = 'processing';
                $this->generationStatus = 'queued';
                $this->dispatch('generation-started');
            }

        } catch (\Exception $e) {
            Log::error('Upload failed', ['error' => $e->getMessage()]);
            $this->error = __('ui.generation_failed');
        }
    }

    /**
     * Captura email durante la pantalla de polling (opcional).
     */
    public function captureEmailDuringPolling()
    {
        $this->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        if (!$this->sessionUuid) return;

        $session = GenerationSession::where('uuid', $this->sessionUuid)->first();
        if (!$session) return;

        $user = User::firstOrCreate(
            ['email' => $this->email],
            [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'locale'     => app()->getLocale(),
            ]
        );
        $user->update(['last_seen_at' => now()]);

        $session->update([
            'user_id'           => $user->id,
            'email_captured_at' => now(),
        ]);

        $this->emailCaptured = true;
    }

    /**
     * Verificar PIN introducido por el usuario.
     */
    public string $pin = '';

    public function verifyPin()
    {
        $this->validate(['pin' => ['required', 'string', 'size:6']]);

        if (!$this->sessionUuid) return;

        $session = GenerationSession::where('uuid', $this->sessionUuid)->first();
        if (!$session) return;

        $attemptsKey = "pin_attempts:{$session->uuid}";
        $attempts = (int) \Illuminate\Support\Facades\Redis::get($attemptsKey);

        if ($attempts >= 5) {
            \Illuminate\Support\Facades\Redis::del("pin:{$session->uuid}");
            $this->error = __('ui.pin_too_many_attempts');
            return;
        }

        $stored = \Illuminate\Support\Facades\Redis::get("pin:{$session->uuid}");
        if (!$stored) {
            $this->error = __('ui.pin_expired');
            return;
        }

        [$storedPin, $storedEmail] = explode(':', $stored, 2);

        // Verificar que el email del PIN coincide con el dueño de la sesión
        if ($session->user && strtolower($session->user->email) !== $storedEmail) {
            $this->error = __('ui.pin_expired');
            return;
        }

        if ($this->pin !== $storedPin) {
            \Illuminate\Support\Facades\Redis::incr($attemptsKey);
            \Illuminate\Support\Facades\Redis::expire($attemptsKey, 3600);
            $this->error = __('ui.pin_incorrect');
            return;
        }

        // PIN correcto — limpiar y generar
        \Illuminate\Support\Facades\Redis::del("pin:{$session->uuid}");
        \Illuminate\Support\Facades\Redis::del($attemptsKey);

        // Marcar verificación en Redis (para acceso posterior sin re-verificar)
        \Illuminate\Support\Facades\Redis::setex(
            "verified:{$session->uuid}:" . request()->ip(),
            86400,
            'true'
        );

        // Lanzar generación IA
        GeneratePortraitJob::dispatch($session);
        $session->update(['status' => 'processing']);

        $this->error = null;
        $this->step = 'processing';
        $this->generationStatus = 'queued';
    }

    /**
     * Reenviar PIN al email.
     */
    public function resendPin()
    {
        $this->pinResent = false;

        if (!$this->sessionUuid) return;

        $session = GenerationSession::where('uuid', $this->sessionUuid)->first();
        if (!$session || !$session->user) return;

        // Usar siempre el email del servidor (no el del cliente Livewire)
        $serverEmail = $session->user->email;

        $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        \Illuminate\Support\Facades\Redis::setex("pin:{$session->uuid}", 3600, "{$pin}:" . strtolower($serverEmail));
        \Illuminate\Support\Facades\Redis::del("pin_attempts:{$session->uuid}");

        try {
            Mail::raw(
                $this->buildPinEmailBody($pin),
                function ($message) use ($serverEmail) {
                    $message->to($serverEmail)
                        ->subject('GenPic — ' . __('ui.pin_subject'));
                }
            );
            $this->pinResent = true;
        } catch (\Exception $e) {
            Log::warning('Resend PIN failed', ['error' => $e->getMessage()]);
            $this->error = __('ui.pin_resend_failed');
        }
    }

    public function checkStatus()
    {
        if (!$this->sessionUuid) return;

        $session = GenerationSession::where('uuid', $this->sessionUuid)->first();
        if (!$session) return;

        $generation = $session->latestGeneration;
        $this->generationStatus = $generation?->status ?? $session->status;

        if ($this->generationStatus === 'watermarked' || $session->status === 'watermarked') {
            $this->redirect(locale_route('create.preview', $session));
        }

        if ($session->status === 'failed') {
            $this->error = __('ui.generation_failed');
            $this->step = 'upload';
        }
    }

    private function buildPinEmailBody(string $pin): string
    {
        return __('ui.pin_code_is') . "\n\n"
            . "    {$pin}\n\n"
            . __('ui.pin_enter_portrait') . "\n"
            . __('ui.pin_valid_60') . "\n\n"
            . __('ui.pin_ignore') . "\n\n"
            . "— GenPic.com";
    }

    /**
     * Notificar a admins de una violacion de contenido (rate-limited por IP).
     */
    private function notifyAdminContentViolation(ContentViolation $violation, int $banHours): void
    {
        try {
            $cacheKey = "admin_notified_violation_{$violation->ip_address}";
            if (Cache::has($cacheKey)) {
                return;
            }

            $adminEmails = config('genpic.admin_emails', []);
            if (empty($adminEmails)) {
                return;
            }

            $mail = new ContentViolationAdminMail(
                ipAddress: $violation->ip_address,
                email: $violation->email,
                userAgent: $violation->user_agent,
                violationType: $violation->violation_type,
                violationScore: $violation->violation_score,
                fileHash: $violation->file_hash,
                provider: $violation->provider,
                ipBanned: true,
                banHours: $banHours,
            );

            foreach ($adminEmails as $email) {
                Mail::to($email)->send($mail);
            }

            Cache::put($cacheKey, true, 900); // 15 min
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar email admin de violacion', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.photo-uploader');
    }
}
