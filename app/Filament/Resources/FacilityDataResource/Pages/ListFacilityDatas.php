<?php

namespace App\Filament\Resources\FacilityDataResource\Pages;

use App\Filament\Resources\FacilityDataResource;
use Filament\Resources\Pages\ListRecords;

class ListFacilityDatas extends ListRecords
{
    protected static string $resource = FacilityDataResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
