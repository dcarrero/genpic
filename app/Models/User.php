<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'is_admin',
        'locale',
        'country_code',
        'timezone',
        'import_source',
        'import_batch',
        'interested_in_print',
        'stripe_customer_id',
        'marketing_opt_in',
        'marketing_opt_out_at',
        'ip_address',
        'user_agent',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'last_seen_at'         => 'datetime',
            'marketing_opt_out_at' => 'datetime',
            'interested_in_print'  => 'boolean',
            'marketing_opt_in'     => 'boolean',
            'is_admin'             => 'boolean',
        ];
    }

    /**
     * Comprueba si el usuario se ha dado de baja de emails de marketing.
     */
    public function hasOptedOutOfMarketing(): bool
    {
        return $this->marketing_opt_out_at !== null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $adminEmails = config('genpic.admin_emails', []);

        return in_array($this->email, $adminEmails, true);
    }

    public function isAdmin(): bool
    {
        return $this->canAccessPanel(app(\Filament\Panel::class));
    }

    /**
     * Filament necesita un name. Usamos la parte local del email.
     */
    public function getNameAttribute(): string
    {
        if ($this->first_name) {
            return trim($this->first_name . ' ' . ($this->last_name ?? ''));
        }
        return explode('@', $this->email)[0];
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function generationSessions(): HasMany
    {
        return $this->hasMany(GenerationSession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
