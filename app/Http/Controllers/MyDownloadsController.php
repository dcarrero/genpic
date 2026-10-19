<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class MyDownloadsController extends Controller
{
    private const TOKEN_TTL = 3600;    // 60 min
    private const PIN_TTL = 3600;      // 60 min
    private const PIN_LENGTH = 6;
    private const MAX_ATTEMPTS = 5;

    /**
     * Paso 1: Formulario pedir email.
     */
    public function showForm()
    {
        return view('downloads.email');
    }

    /**
     * Paso 2: Enviar email con PIN de verificación.
     */
    public function sendPin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $email = strtolower($request->email);
        $user = User::where('email', $email)->first();

        // No revelar si el email existe o no (seguridad)
        if (!$user) {
            return redirect()->route(locale_route_name('downloads.pin'))
                ->with('email', $email)
                ->with('pin_sent', true);
        }

        // Generar PIN
        $pin = str_pad(random_int(0, 999999), self::PIN_LENGTH, '0', STR_PAD_LEFT);

        // Guardar en Redis
        Redis::setex("downloads_pin:{$email}", self::PIN_TTL, $pin);
        Redis::del("downloads_pin_attempts:{$email}");

        // Enviar PIN por email
        Mail::raw(
            $this->buildPinEmailBody($pin),
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('GenPic — ' . __('ui.downloads_pin_subject'));
            }
        );

        return redirect()->route(locale_route_name('downloads.pin'))
            ->with('email', $email)
            ->with('pin_sent', true);
    }

    /**
     * Paso 3: Formulario PIN.
     */
    public function showPinForm()
    {
        return view('downloads.pin', [
            'email'    => session('email', ''),
            'pin_sent' => session('pin_sent', false),
        ]);
    }

    /**
     * Paso 4: Verificar PIN → generar token → redirigir a lista.
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'pin'   => ['required', 'string', 'size:' . self::PIN_LENGTH],
        ]);

        $email = strtolower($request->email);

        // Rate limit
        $attemptsKey = "downloads_pin_attempts:{$email}";
        $attempts = (int) Redis::get($attemptsKey);
        if ($attempts >= self::MAX_ATTEMPTS) {
            Redis::del("downloads_pin:{$email}");
            return back()->withInput()->withErrors(['pin' => __('ui.downloads_too_many_attempts')]);
        }

        // Verificar PIN
        $storedPin = Redis::get("downloads_pin:{$email}");
        if (!$storedPin) {
            return back()->withInput()->withErrors(['pin' => __('ui.downloads_pin_expired')]);
        }

        if ($request->pin !== $storedPin) {
            Redis::incr($attemptsKey);
            Redis::expire($attemptsKey, self::PIN_TTL);
            return back()->withInput()->withErrors(['pin' => __('ui.downloads_pin_wrong')]);
        }

        // PIN correcto → generar token de acceso
        Redis::del("downloads_pin:{$email}");
        Redis::del($attemptsKey);

        $token = Str::random(64);
        Redis::setex("downloads_token:{$token}", self::TOKEN_TTL, $email);

        return redirect()->route(locale_route_name('downloads.list'), ['token' => $token]);
    }

    /**
     * Paso 5: Mostrar todas las sesiones/orders del usuario.
     */
    public function showList(Request $request)
    {
        $token = $request->query('token');
        if (!$token) abort(403);

        $email = Redis::get("downloads_token:{$token}");
        if (!$email) {
            return redirect()->route(locale_route_name('downloads.form'))
                ->withErrors(['expired' => __('ui.downloads_token_expired')]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) abort(404);

        // Todas las sesiones del usuario con sus generaciones y orders
        $sessions = $user->generationSessions()
            ->with(['latestGeneration', 'orders.items', 'artisticStyle'])
            ->whereNull('purged_at')
            ->orderByDesc('created_at')
            ->get();

        return view('downloads.list', compact('user', 'sessions', 'token'));
    }

    private function buildPinEmailBody(string $pin): string
    {
        return __('ui.pin_code_is') . "\n\n"
            . "    {$pin}\n\n"
            . __('ui.pin_enter_downloads') . "\n"
            . __('ui.pin_valid_60') . "\n\n"
            . __('ui.pin_ignore') . "\n\n"
            . "— GenPic.com";
    }
}
