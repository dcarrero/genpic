<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Configure Horizon authorization.
     * Overrides parent to allow local access without auth.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request) {
            if (app()->environment('local')) {
                return true;
            }

            return Gate::check('viewHorizon');
        });
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            $adminEmails = config('genpic.admin_emails', []);
            return in_array(optional($user)->email, $adminEmails, true);
        });
    }
}
