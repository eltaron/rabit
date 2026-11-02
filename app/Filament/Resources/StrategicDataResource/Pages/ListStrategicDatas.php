<?php

namespace App\Filament\Resources\StrategicDataResource\Pages;

use App\Filament\Resources\StrategicDataResource;
use Filament\Resources\Pages\ListRecords;

class ListStrategicDatas extends ListRecords
{
    protected static string $resource = StrategicDataResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
