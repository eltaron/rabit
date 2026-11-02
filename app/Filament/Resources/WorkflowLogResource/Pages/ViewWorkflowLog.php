<?php

namespace App\Filament\Resources\WorkflowLogResource\Pages;

use App\Filament\Resources\WorkflowLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkflowLog extends ViewRecord
{
    protected static string $resource = WorkflowLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
