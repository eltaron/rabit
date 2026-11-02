<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FormSubmissionResource;
use App\Models\FormSubmission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestSubmissionsTable extends TableWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $status = FormSubmissionResource::fields()['status'];

        return $table
            ->heading('أحدث إرسالات النماذج')
            ->query(FormSubmission::query()->with(['formTemplate', 'createdBy'])->latest())
            ->columns([
                TextColumn::make('reference_number')->label('الرقم المرجعي'),
                TextColumn::make('formTemplate.name')->label('النموذج')->limit(40),
                TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => $status['map'][$state] ?? $state)
                    ->color(fn ($state) => $status['colors'][$state] ?? 'gray'),
                TextColumn::make('createdBy.name')->label('أنشأه'),
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i'),
            ])
            ->recordUrl(fn (FormSubmission $record) => FormSubmissionResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
