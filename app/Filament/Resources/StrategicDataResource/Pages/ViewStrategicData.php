<?php

namespace App\Filament\Resources\StrategicDataResource\Pages;

use App\Filament\Resources\StrategicDataResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStrategicData extends ViewRecord
{
    protected static string $resource = StrategicDataResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
