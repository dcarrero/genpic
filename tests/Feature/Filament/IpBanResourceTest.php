<?php

namespace Tests\Feature\Filament;

use App\Models\IpBan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpBanResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // IP restriction: empty list = allow all (dev mode)
        config(['genpic.admin_allowed_ips' => []]);

        // Admin email whitelist
        config(['genpic.admin_emails' => ['admin@test.com']]);
    }

    /**
     * Regression: un ban activo (unbanned_at = NULL) no debe causar 500.
     * Antes del fix, Filament pasaba el default '—' por Carbon::parse() → DateMalformedStringException.
     */
    public function test_ip_bans_index_does_not_crash_with_active_ban_null_unbanned_at(): void
    {
        $admin = User::factory()->create(['email' => 'admin@test.com']);

        // Ban activo: unbanned_at es NULL — este es el caso que crasheaba
        IpBan::create([
            'ip_address' => '1.2.3.4',
            'reason' => 'content_violation',
            'banned_at' => now(),
            'expires_at' => now()->addHours(72),
        ]);

        $this->actingAs($admin)
            ->get('/'.config('genpic.admin_path').'/ip-bans')
            ->assertOk();
    }
}
