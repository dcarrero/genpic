<?php

namespace App\Filament\Resources\ArtisticStyleResource\Pages;

use App\Filament\Resources\ArtisticStyleResource;
use Filament\Resources\Pages\ListRecords;

class ListArtisticStyles extends ListRecords
{
    protected static string $resource = ArtisticStyleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
