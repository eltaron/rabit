<?php

namespace App\Filament\Resources\OrgUnitResource\Pages;

use App\Filament\Resources\OrgUnitResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrgUnit extends ViewRecord
{
    protected static string $resource = OrgUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
