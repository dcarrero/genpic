<?php

namespace App\Filament\Resources\ContentViolationResource\Pages;

use App\Filament\Resources\ContentViolationResource;
use Filament\Resources\Pages\ListRecords;

class ListContentViolations extends ListRecords
{
    protected static string $resource = ContentViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
