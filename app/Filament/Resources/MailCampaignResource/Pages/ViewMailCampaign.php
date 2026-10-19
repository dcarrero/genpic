<?php

namespace App\Filament\Resources\MailCampaignResource\Pages;

use App\Filament\Resources\MailCampaignResource;
use App\Jobs\SendCampaignBatchJob;
use App\Models\MailCampaign;
use App\Models\MailSend;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ViewMailCampaign extends ViewRecord
{
    protected static string $resource = MailCampaignResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Campaña')->schema([
                Infolists\Components\TextEntry::make('name')->label('Nombre'),
                Infolists\Components\TextEntry::make('status')->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray', 'sending' => 'warning', 'paused' => 'info', 'completed' => 'success', default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('coupon_code')->label('Cupón'),
                Infolists\Components\TextEntry::make('target_source')->label('Grupo')->default('Todos'),
                Infolists\Components\TextEntry::make('batch_size')->label('Tamaño lote'),
            ])->columns(3),

            Infolists\Components\Section::make('Progreso')->schema([
                Infolists\Components\TextEntry::make('total_recipients')->label('Total destinatarios'),
                Infolists\Components\TextEntry::make('sent_count')->label('Enviados'),
                Infolists\Components\TextEntry::make('failed_count')->label('Fallos'),
                Infolists\Components\TextEntry::make('progress')
                    ->label('Progreso')
                    ->getStateUsing(fn ($record) => $record->progressPercent() . '%'),
                Infolists\Components\TextEntry::make('pending')
                    ->label('Pendientes')
                    ->getStateUsing(fn ($record) => $record->pendingSends()->count()),
            ])->columns(5),

            Infolists\Components\Section::make('Distribución por idioma')->schema([
                Infolists\Components\TextEntry::make('lang_stats')
                    ->label('')
                    ->getStateUsing(function ($record) {
                        $stats = $record->sends()
                            ->select('detected_language', DB::raw('count(*) as total'), DB::raw("SUM(status = 'sent') as sent"), DB::raw("SUM(status = 'pending') as pending"))
                            ->groupBy('detected_language')
                            ->get();
                        return $stats->map(fn ($s) => strtoupper($s->detected_language) . ": {$s->total} total, {$s->sent} enviados, {$s->pending} pendientes")->implode(' | ');
                    }),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            // Preparar envío — crea mail_sends a partir de users del grupo
            Actions\Action::make('prepare')
                ->label('Preparar destinatarios')
                ->icon('heroicon-o-user-group')
                ->color('gray')
                ->visible(fn () => $this->record->status === 'draft' && $this->record->total_recipients === 0)
                ->requiresConfirmation()
                ->modalDescription(fn () => $this->prepareSummary())
                ->action(function () {
                    $campaign = $this->record;
                    $query = User::whereNotNull('email')
                        ->where('email', 'not like', 'pending_%')
                        ->whereNull('marketing_opt_out_at');

                    if ($campaign->target_source) {
                        $query->where('import_source', $campaign->target_source);
                    }

                    $users = $query->get();
                    $created = 0;

                    foreach ($users as $user) {
                        $lang = MailCampaign::detectLanguage($user->country_code);
                        MailSend::firstOrCreate(
                            ['campaign_id' => $campaign->id, 'user_id' => $user->id],
                            ['email' => $user->email, 'detected_language' => $lang]
                        );
                        $created++;
                    }

                    $campaign->update(['total_recipients' => $created]);

                    Notification::make()->title("Preparados {$created} destinatarios")->success()->send();
                }),

            // Enviar test
            Actions\Action::make('send_test')
                ->label('Enviar test')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->form([
                    Forms\Components\TextInput::make('test_email')
                        ->label('Email de prueba')
                        ->email()
                        ->required()
                        ->default(fn () => config('genpic.admin_emails')[0] ?? ''),
                    Forms\Components\Select::make('test_locale')
                        ->label('Idioma')
                        ->options(['es' => 'ES', 'en' => 'EN', 'fr' => 'FR', 'pt' => 'PT', 'it' => 'IT'])
                        ->default('es'),
                ])
                ->action(function (array $data) {
                    $campaign = $this->record;
                    $locale = $data['test_locale'];
                    $subject = '[TEST] ' . $campaign->t('subject', $locale);
                    $body = $campaign->t('body', $locale);
                    $unsubscribeUrl = URL::signedRoute('unsubscribe', ['email' => $data['test_email']]);
                    $ctaUrl = config('app.url') . ($locale === 'es' ? '' : "/{$locale}") . '/create';
                    if ($campaign->coupon_code) $ctaUrl .= "?coupon={$campaign->coupon_code}";

                    Mail::html(
                        view('emails.campaign', [
                            'body' => $body, 'couponCode' => $campaign->coupon_code,
                            'ctaUrl' => $ctaUrl, 'unsubscribeUrl' => $unsubscribeUrl,
                            'locale' => $locale, 'userName' => 'Test',
                        ])->render(),
                        fn ($m) => $m->to($data['test_email'])->subject($subject)
                    );

                    Notification::make()->title("Test enviado a {$data['test_email']} ({$locale})")->success()->send();
                }),

            // Enviar siguiente lote
            Actions\Action::make('send_batch')
                ->label(fn () => "Enviar lote ({$this->record->batch_size})")
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['draft', 'sending', 'paused']) && $this->record->total_recipients > 0)
                ->requiresConfirmation()
                ->modalHeading('Enviar siguiente lote')
                ->modalDescription(fn () => "Se enviarán {$this->record->batch_size} emails. Pendientes: {$this->record->pendingSends()->count()}")
                ->action(function () {
                    $campaign = $this->record;

                    if ($campaign->status === 'draft' || $campaign->status === 'paused') {
                        $campaign->update([
                            'status'     => 'sending',
                            'started_at' => $campaign->started_at ?? now(),
                        ]);
                    }

                    SendCampaignBatchJob::dispatch($campaign, $campaign->batch_size);

                    Notification::make()
                        ->title("Lote de {$campaign->batch_size} en cola")
                        ->body('Refresca la página en unos segundos para ver el progreso.')
                        ->success()->send();
                }),

            // Pausar
            Actions\Action::make('pause')
                ->label('Pausar')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'sending')
                ->action(function () {
                    $this->record->update(['status' => 'paused']);
                    Notification::make()->title('Campaña pausada')->warning()->send();
                }),
        ];
    }

    private function prepareSummary(): string
    {
        $campaign = $this->record;
        $query = User::whereNotNull('email')
            ->where('email', 'not like', 'pending_%')
            ->whereNull('marketing_opt_out_at');

        if ($campaign->target_source) {
            $query->where('import_source', $campaign->target_source);
        }

        $total = $query->count();
        $byLang = $query->get()->groupBy(fn ($u) => MailCampaign::detectLanguage($u->country_code))
            ->map->count()
            ->sortDesc();

        $langStr = $byLang->map(fn ($c, $l) => strtoupper($l) . ": {$c}")->implode(', ');

        return "Se prepararán {$total} destinatarios.\n\nDistribución: {$langStr}";
    }
}
