<?php

namespace App\Filament\Resources\MailCampaignResource\Pages;

use App\Filament\Resources\MailCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailCampaign extends EditRecord
{
    protected static string $resource = MailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
