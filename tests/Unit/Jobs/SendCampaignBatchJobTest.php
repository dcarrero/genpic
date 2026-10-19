<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendCampaignBatchJob;
use App\Models\MailCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SendCampaignBatchJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper para insertar un send con status = 'sending'.
     * El enum de mail_sends.status incluye 'sending' desde la migración
     * 2026_06_12_000001_add_sending_to_mail_sends_status_enum.
     */
    private function createSendInSendingStatus(int $campaignId, int $userId, string $email, string $lang = 'es'): int
    {
        return DB::table('mail_sends')->insertGetId([
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'email' => $email,
            'detected_language' => $lang,
            'status' => 'sending',
        ]);
    }

    /**
     * Verifica que failed() revierte 'sending' → 'pending' solo para los sends
     * de la campaña afectada, sin tocar sends de otras campañas.
     */
    public function test_failed_reverts_sending_sends_to_pending_for_the_correct_campaign(): void
    {
        // Campaña afectada
        $campaign = MailCampaign::create([
            'name' => 'Campaña test',
            'subject' => ['es' => 'Asunto', 'en' => 'Subject'],
            'body' => ['es' => '<p>Cuerpo</p>', 'en' => '<p>Body</p>'],
            'status' => 'sending',
        ]);

        // Campaña de control (aislamiento)
        $otherCampaign = MailCampaign::create([
            'name' => 'Otra campaña',
            'subject' => ['es' => 'Otro asunto', 'en' => 'Other subject'],
            'body' => ['es' => '<p>Otro</p>', 'en' => '<p>Other</p>'],
            'status' => 'sending',
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $userControl = User::factory()->create();

        // 3 sends de la campaña afectada en estado 'sending'
        $id1 = $this->createSendInSendingStatus($campaign->id, $user1->id, $user1->email);
        $id2 = $this->createSendInSendingStatus($campaign->id, $user2->id, $user2->email);
        $id3 = $this->createSendInSendingStatus($campaign->id, $user3->id, $user3->email, 'en');

        // 1 send de otra campaña en 'sending' — no debe cambiar (control de aislamiento)
        $controlId = $this->createSendInSendingStatus($otherCampaign->id, $userControl->id, $userControl->email, 'en');

        // Confirmar estado inicial
        $this->assertEquals('sending', DB::table('mail_sends')->where('id', $id1)->value('status'));
        $this->assertEquals('sending', DB::table('mail_sends')->where('id', $controlId)->value('status'));

        // Ejecutar el método bajo prueba
        $job = new SendCampaignBatchJob($campaign);
        $job->failed(new \Exception('smtp timeout'));

        // Los 3 sends de la campaña afectada deben haber vuelto a 'pending'
        $this->assertEquals('pending', DB::table('mail_sends')->where('id', $id1)->value('status'), "send1 debe ser 'pending'");
        $this->assertEquals('pending', DB::table('mail_sends')->where('id', $id2)->value('status'), "send2 debe ser 'pending'");
        $this->assertEquals('pending', DB::table('mail_sends')->where('id', $id3)->value('status'), "send3 debe ser 'pending'");

        // El send de la otra campaña NO debe cambiar
        $this->assertEquals('sending', DB::table('mail_sends')->where('id', $controlId)->value('status'), "send de otra campaña debe seguir en 'sending'");
    }
}
