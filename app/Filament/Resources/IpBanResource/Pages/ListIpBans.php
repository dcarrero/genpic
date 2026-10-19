<?php

namespace App\Filament\Resources\IpBanResource\Pages;

use App\Filament\Resources\IpBanResource;
use Filament\Resources\Pages\ListRecords;

class ListIpBans extends ListRecords
{
    protected static string $resource = IpBanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
