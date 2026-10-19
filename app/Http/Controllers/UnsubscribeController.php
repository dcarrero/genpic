<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    /**
     * Muestra la página de confirmación de desuscripción.
     * Requiere signed URL con parámetro email.
     */
    public function show(Request $request)
    {
        abort_unless($request->hasValidSignature(), 403);

        $email = $request->query('email');
        abort_unless($email, 404);

        $user = User::where('email', $email)->first();
        abort_unless($user, 404);

        return view('unsubscribe.show', [
            'user'      => $user,
            'email'     => $email,
            'optedOut'  => $user->hasOptedOutOfMarketing(),
        ]);
    }

    /**
     * Procesa la desuscripción o re-suscripción.
     */
    public function process(Request $request)
    {
        abort_unless($request->hasValidSignature(), 403);

        $email = $request->query('email');
        abort_unless($email, 404);

        $user = User::where('email', $email)->first();
        abort_unless($user, 404);

        $action = $request->input('action', 'unsubscribe');

        if ($action === 'resubscribe') {
            $user->update(['marketing_opt_out_at' => null]);
        } else {
            $user->update(['marketing_opt_out_at' => now()]);
        }

        return view('unsubscribe.show', [
            'user'      => $user->fresh(),
            'email'     => $email,
            'optedOut'  => $user->fresh()->hasOptedOutOfMarketing(),
            'processed' => true,
            'action'    => $action,
        ]);
    }

    /**
     * Formulario público de desuscripción para face.co (sin signed URL).
     */
    public function facecoForm()
    {
        return view('unsubscribe.faceco');
    }

    /**
     * Procesa la desuscripción pública de face.co.
     */
    public function facecoProcess(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return view('unsubscribe.faceco', [
                'error' => __('ui.unsub_not_found'),
            ]);
        }

        $user->update(['marketing_opt_out_at' => now()]);

        // También marcar los mail_sends pendientes como no enviables
        \App\Models\MailSend::where('email', $email)
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'error_message' => 'User unsubscribed via /facecounsus']);

        return view('unsubscribe.faceco', [
            'processed' => true,
            'action'    => 'unsubscribe',
        ]);
    }
}
