<?php

namespace App\Filament\Resources\GalleryExampleResource\Pages;

use App\Filament\Resources\GalleryExampleResource;
use Filament\Resources\Pages\EditRecord;

class EditGalleryExample extends EditRecord
{
    protected static string $resource = GalleryExampleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
