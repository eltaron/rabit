<?php

namespace App\Filament\Resources\FacilityDataResource\Pages;

use App\Filament\Resources\FacilityDataResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFacilityData extends ViewRecord
{
    protected static string $resource = FacilityDataResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
