<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePortraitJob;
use App\Mail\SessionCreatedMail;
use App\Models\ArtisticStyle;
use App\Models\GenerationSession;
use App\Models\User;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class GenerationController extends Controller
{
    public function index()
    {
        $styles = \App\Services\CachedQueries::activeStyles();
        $title = __('ui.seo_create_title');
        $description = __('ui.seo_create_description');

        return view('generator.index', compact('styles', 'title', 'description'));
    }

    public function upload(Request $request, StorageService $storage)
    {
        $request->validate([
            'photo'     => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'style_key' => ['required', 'string', 'exists:artistic_styles,style_key'],
        ]);

        $style = ArtisticStyle::where('style_key', $request->style_key)->firstOrFail();

        // Guardar foto original en el disco correcto
        $file = $request->file('photo');
        $path = $storage->store(
            file_get_contents($file->getRealPath()),
            'originals',
            'upload-' . now()->format('Ymd'),
            $file->getClientOriginalExtension() ?: 'jpg'
        );

        // Crear sesión
        $session = GenerationSession::create([
            'style_key'           => $style->style_key,
            'artistic_style_id'   => $style->id,
            'original_photo_path' => $path,
            'original_filename'   => $file->getClientOriginalName(),
            'original_size_bytes' => $file->getSize(),
            'ip_address'          => $request->ip(),
            'locale'              => 'es',
            'status'              => 'pending',
        ]);

        // Face detection se realiza client-side con face-api.js
        // Este endpoint es fallback/API — confiamos en el cliente para MVP
        $session->update(['face_detected' => true]);

        GeneratePortraitJob::dispatch($session);

        return response()->json([
            'session_uuid' => $session->uuid,
            'status'       => 'processing',
        ]);
    }

    public function captureEmail(Request $request)
    {
        $request->validate([
            'email'        => ['required', 'email:rfc,dns', 'max:255'],
            'session_uuid' => ['required', 'uuid', 'exists:generation_sessions,uuid'],
        ]);

        $session = GenerationSession::where('uuid', $request->session_uuid)->firstOrFail();

        // No permitir reasignar sesión ya reclamada por otro email
        if ($session->user_id && $session->user && $session->user->email !== $request->email) {
            return response()->json(['ok' => true]); // Silencioso para no filtrar info
        }

        // Buscar o crear usuario por email
        $user = User::firstOrCreate(
            ['email' => $request->email],
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'locale'     => app()->getLocale(),
            ]
        );

        $user->update(['last_seen_at' => now()]);

        $session->update([
            'user_id'           => $user->id,
            'email_captured_at' => now(),
        ]);

        if ($session->user) {
            Mail::to($session->user)->send(new SessionCreatedMail($session));
        }

        return response()->json(['ok' => true]);
    }

    public function preview(GenerationSession $session)
    {
        $session->load(['latestGeneration', 'artisticStyle']);

        return view('generator.preview', compact('session'));
    }

    /**
     * Endpoint de polling para Livewire — devuelve estado actual.
     */
    public function status(GenerationSession $session)
    {
        $generation = $session->latestGeneration;

        return response()->json([
            'session_status'    => $session->status,
            'generation_status' => $generation?->status,
            'has_watermark'     => (bool) $generation?->watermarked_image_path,
        ]);
    }

    /**
     * Acceso desde email — redirige al flujo de verificación PIN.
     */
    public function resumeSession(Request $request, GenerationSession $session)
    {
        if ($session->isExpired()) {
            abort(410, __('ui.expired'));
        }

        return redirect()->route(locale_route_name('portrait.access'), $session);
    }
}
