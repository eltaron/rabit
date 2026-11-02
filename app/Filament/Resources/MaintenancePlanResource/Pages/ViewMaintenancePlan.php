<?php

namespace App\Filament\Resources\MaintenancePlanResource\Pages;

use App\Filament\Resources\MaintenancePlanResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenancePlan extends ViewRecord
{
    protected static string $resource = MaintenancePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
