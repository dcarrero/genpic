<?php

namespace App\Filament\Resources\ArtisticStyleResource\Pages;

use App\Filament\Resources\ArtisticStyleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArtisticStyle extends EditRecord
{
    protected static string $resource = ArtisticStyleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
