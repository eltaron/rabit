<?php

namespace App\Filament\Resources\OrgUnitResource\Pages;

use App\Filament\Resources\OrgUnitResource;
use Filament\Resources\Pages\ListRecords;

class ListOrgUnits extends ListRecords
{
    protected static string $resource = OrgUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
