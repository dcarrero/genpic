<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePortraitJob;
use App\Models\GenerationSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class PortraitAccessController extends Controller
{
    private const PIN_TTL = 3600;       // 60 minutos

    private const VERIFIED_TTL = 86400; // 24 horas

    private const PIN_LENGTH = 6;

    private const MAX_ATTEMPTS = 5;

    /**
     * Paso 1: Mostrar formulario de email.
     */
    public function showEmailForm(GenerationSession $session)
    {
        if ($session->isExpired()) {
            abort(410, __('ui.expired'));
        }

        // Si ya está verificado, ir directo al preview
        if ($this->isVerified($session, request())) {
            return redirect()->route(locale_route_name('create.preview'), $session);
        }

        // Si PIN desactivado, mostrar formulario de email simple
        // (solo pide email para confirmar identidad, sin PIN)
        if (! config('genpic.require_pin')) {
            return view('portrait.email-simple', compact('session'));
        }

        return view('portrait.email', compact('session'));
    }

    /**
     * Paso 2: Verificar email y enviar PIN.
     */
    public function sendPin(Request $request, GenerationSession $session)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Verificar que el email coincide con el de la sesión
        if (! $session->user || strtolower($request->email) !== strtolower($session->user->email)) {
            return back()->withErrors(['email' => __('ui.portrait_email_mismatch')]);
        }

        // Si PIN desactivado: verificar email → marcar como verificado → generar → preview
        if (! config('genpic.require_pin')) {
            Redis::setex(
                "verified:{$session->uuid}:".$request->ip(),
                self::VERIFIED_TTL,
                'true'
            );

            // Lanzar generación si aún no se ha hecho (lock atómico)
            $this->dispatchGenerationIfPending($session);

            return redirect()->route(locale_route_name('create.preview'), $session);
        }

        // Generar PIN
        $pin = str_pad(random_int(0, 999999), self::PIN_LENGTH, '0', STR_PAD_LEFT);

        // Guardar en Redis: pin:{uuid} → {pin}:{email} — TTL 60 min
        Redis::setex(
            "pin:{$session->uuid}",
            self::PIN_TTL,
            "{$pin}:".strtolower($request->email)
        );

        // Resetear intentos
        Redis::del("pin_attempts:{$session->uuid}");

        // Enviar email con PIN
        Mail::raw(
            $this->buildPinEmailBody($pin, $session),
            function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('GenPic — '.__('ui.pin_subject'));
            }
        );

        return redirect()->route(locale_route_name('portrait.verify'), $session)
            ->with('pin_sent', true)
            ->with('email', $request->email);
    }

    /**
     * Paso 3: Mostrar formulario de PIN.
     */
    public function showPinForm(GenerationSession $session)
    {
        return view('portrait.pin', [
            'session' => $session,
            'email' => session('email', ''),
            'pin_sent' => session('pin_sent', false),
        ]);
    }

    /**
     * Paso 4: Verificar PIN → acceso al retrato.
     */
    public function verifyPin(Request $request, GenerationSession $session)
    {
        $request->validate([
            'pin' => ['required', 'string', 'size:'.self::PIN_LENGTH],
        ]);

        // Rate limit de intentos
        $attemptsKey = "pin_attempts:{$session->uuid}";
        $attempts = (int) Redis::get($attemptsKey);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Redis::del("pin:{$session->uuid}");

            return back()->withErrors(['pin' => app()->getLocale() === 'es'
                ? 'Demasiados intentos. Solicita un nuevo código.'
                : 'Too many attempts. Please request a new code.',
            ]);
        }

        // Obtener PIN guardado
        $stored = Redis::get("pin:{$session->uuid}");
        if (! $stored) {
            return back()->withErrors(['pin' => app()->getLocale() === 'es'
                ? 'El código ha expirado. Solicita uno nuevo.'
                : 'The code has expired. Please request a new one.',
            ]);
        }

        [$storedPin, $storedEmail] = explode(':', $stored, 2);

        if (! hash_equals($storedPin, (string) $request->pin)) {
            Redis::incr($attemptsKey);
            Redis::expire($attemptsKey, self::PIN_TTL);

            return back()->withErrors(['pin' => app()->getLocale() === 'es'
                ? 'Código incorrecto. Inténtalo de nuevo.'
                : 'Incorrect code. Please try again.',
            ]);
        }

        // PIN correcto → marcar como verificado
        Redis::setex(
            "verified:{$session->uuid}:".$request->ip(),
            self::VERIFIED_TTL,
            'true'
        );

        // Limpiar PIN usado
        Redis::del("pin:{$session->uuid}");
        Redis::del($attemptsKey);

        // AHORA sí lanzar la generación IA (solo tras verificar email real)
        $this->dispatchGenerationIfPending($session);

        return redirect()->route(locale_route_name('create.preview'), $session);
    }

    /**
     * Comprobar si el acceso está verificado.
     */
    public static function isVerified(GenerationSession $session, Request $request): bool
    {
        return (bool) Redis::get("verified:{$session->uuid}:".$request->ip());
    }

    /**
     * Dispatch generación solo si la sesión está pendiente (atómico con lock).
     */
    private function dispatchGenerationIfPending(GenerationSession $session): void
    {
        DB::transaction(function () use ($session) {
            $locked = GenerationSession::where('id', $session->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            $locked->update(['status' => 'processing']);
            GeneratePortraitJob::dispatch($locked);
        });
    }

    private function buildPinEmailBody(string $pin, GenerationSession $session): string
    {
        return __('ui.pin_code_is')."\n\n"
            ."    {$pin}\n\n"
            .__('ui.pin_enter_portrait')."\n"
            .__('ui.pin_valid_60')."\n\n"
            .__('ui.pin_ignore')."\n\n"
            .'— GenPic.com';
    }
}
