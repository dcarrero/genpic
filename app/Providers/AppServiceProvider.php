<?php

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Helper Blade para rutas localizadas
        \Illuminate\Support\Facades\Blade::directive('localeRoute', function ($expression) {
            return "<?php echo locale_route($expression); ?>";
        });

        // Header ACUMBAMAIL-SMTPAPI en todos los emails para tracking en Acumbamail
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $headers = $event->message->getHeaders();

            // Si ya tiene categoría (ej: campañas), no sobreescribir
            if ($headers->has('ACUMBAMAIL-SMTPAPI')) {
                return;
            }

            // Detectar categoría por el Mailable o el asunto
            $category = 'genpic-transactional';

            $subject = $event->message->getSubject() ?? '';
            if (str_contains($subject, 'PIN') || str_contains($subject, 'código')) {
                $category = 'genpic-pin';
            } elseif (str_contains($subject, 'retrato') || str_contains($subject, 'portrait') || str_contains($subject, 'ritratto')) {
                $category = 'genpic-session';
            } elseif (str_contains($subject, 'descarga') || str_contains($subject, 'download') || str_contains($subject, 'scarica')) {
                $category = 'genpic-download';
            } elseif (str_contains($subject, 'gratis') || str_contains($subject, 'free') || str_contains($subject, 'gratuit')) {
                $category = 'genpic-free-download';
            } elseif (str_contains($subject, 'expira') || str_contains($subject, 'expir')) {
                $category = 'genpic-expiring';
            } elseif (str_contains($subject, 'proveedor') || str_contains($subject, 'provider')) {
                $category = 'genpic-admin-alert';
            }

            $headers->addTextHeader('ACUMBAMAIL-SMTPAPI', $category);
        });
    }
}
