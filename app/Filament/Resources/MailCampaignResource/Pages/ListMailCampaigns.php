<?php

namespace App\Filament\Resources\MailCampaignResource\Pages;

use App\Filament\Resources\MailCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMailCampaigns extends ListRecords
{
    protected static string $resource = MailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
