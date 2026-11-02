<?php

namespace App\Filament\Resources\SectorDataResource\Pages;

use App\Filament\Resources\SectorDataResource;
use Filament\Resources\Pages\ListRecords;

class ListSectorDatas extends ListRecords
{
    protected static string $resource = SectorDataResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
