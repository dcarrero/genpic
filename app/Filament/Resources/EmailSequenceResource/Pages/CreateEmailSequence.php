<?php

namespace App\Filament\Resources\EmailSequenceResource\Pages;

use App\Filament\Resources\EmailSequenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailSequence extends CreateRecord
{
    protected static string $resource = EmailSequenceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
