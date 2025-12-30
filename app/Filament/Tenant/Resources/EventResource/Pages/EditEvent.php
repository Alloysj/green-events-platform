<?php

namespace App\Filament\Tenant\Resources\EventResource\Pages;

use App\Filament\Tenant\Resources\EventResource;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;
}
