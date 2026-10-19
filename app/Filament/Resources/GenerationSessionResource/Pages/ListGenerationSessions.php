<?php

namespace App\Filament\Resources\GenerationSessionResource\Pages;

use App\Filament\Resources\GenerationSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListGenerationSessions extends ListRecords
{
    protected static string $resource = GenerationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
