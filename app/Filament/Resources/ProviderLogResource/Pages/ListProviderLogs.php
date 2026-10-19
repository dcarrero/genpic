<?php

namespace App\Filament\Resources\ProviderLogResource\Pages;

use App\Filament\Resources\ProviderLogResource;
use Filament\Resources\Pages\ListRecords;

class ListProviderLogs extends ListRecords
{
    protected static string $resource = ProviderLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
