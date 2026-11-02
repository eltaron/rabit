<?php

namespace App\Filament\Resources\WorkflowLogResource\Pages;

use App\Filament\Resources\WorkflowLogResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowLogs extends ListRecords
{
    protected static string $resource = WorkflowLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
