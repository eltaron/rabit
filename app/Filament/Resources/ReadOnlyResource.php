<?php

namespace App\Filament\Resources;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * مورد للعرض فقط داخل لوحة Octagon: لا إضافة ولا تعديل ولا حذف.
 *
 * كل مورد يعرّف fields() بالشكل:
 *   'column' => [
 *       'label'  => 'الاسم',
 *       'type'   => 'text|badge|date|datetime|bool|number|json|long',
 *       'search' => true,      // بحث نصي
 *       'filter' => true,      // فلتر بالقيم المميزة (أو مصفوفة خيارات)
 *       'list'   => false,     // إخفاء من الجدول (يظهر في صفحة العرض فقط)
 *       'hidden' => true,      // عمود مخفي افتراضياً ويمكن إظهاره
 *       'map'    => [...],     // ترجمة القيم
 *       'colors' => [...],     // ألوان الـ badge حسب القيمة
 *   ]
 */
abstract class ReadOnlyResource extends Resource
{
    /** @return array<string, array<string, mixed>> */
    abstract public static function fields(): array;

    // ------------------------------------------------------------
    // صلاحيات: عرض فقط
    // ------------------------------------------------------------
    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore(Model $record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    public static function canReplicate(Model $record): bool
    {
        return false;
    }

    public static function canReorder(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::query()->count());
    }

    // ------------------------------------------------------------
    // الجدول
    // ------------------------------------------------------------
    public static function table(Table $table): Table
    {
        $columns = [];
        $filters = [];

        foreach (static::fields() as $name => $f) {
            $type = $f['type'] ?? 'text';

            if (($f['list'] ?? true) !== false && ! in_array($type, ['json', 'long'], true)) {
                $columns[] = static::makeColumn($name, $f);
            }

            if (! empty($f['filter'])) {
                $filters[] = static::makeFilter($name, $f);
            }
        }

        return $table
            ->columns($columns)
            ->filters($filters)
            ->recordActions([ViewAction::make()])
            ->toolbarActions([])
            ->defaultSort(static::defaultSortColumn(), 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->striped();
    }

    protected static function defaultSortColumn(): string
    {
        return 'id';
    }

    protected static function makeColumn(string $name, array $f): TextColumn|IconColumn
    {
        $type = $f['type'] ?? 'text';
        $label = $f['label'] ?? $name;

        if ($type === 'bool') {
            return IconColumn::make($name)->label($label)->boolean()->sortable();
        }

        $col = TextColumn::make($name)->label($label)->placeholder('—');

        if (! str_contains($name, '.')) {
            $col->sortable();
        }

        match ($type) {
            'date' => $col->date('Y-m-d'),
            'datetime' => $col->dateTime('Y-m-d H:i'),
            'number' => $col->numeric(),
            'badge' => $col->badge(),
            default => $col->limit(40)->tooltip(fn ($state) => is_string($state) && mb_strlen($state) > 40 ? $state : null),
        };

        if (! empty($f['map'])) {
            $map = $f['map'];
            $col->formatStateUsing(fn ($state) => $map[$state] ?? $state);
        }

        if (! empty($f['colors'])) {
            $colors = $f['colors'];
            $col->color(fn ($state) => $colors[$state] ?? 'gray');
        }

        if (! empty($f['search'])) {
            $col->searchable();
        }

        if (! empty($f['hidden'])) {
            $col->toggleable(isToggledHiddenByDefault: true);
        } else {
            $col->toggleable();
        }

        return $col;
    }

    protected static function makeFilter(string $name, array $f): SelectFilter|TernaryFilter
    {
        $label = $f['label'] ?? $name;

        if (($f['type'] ?? null) === 'bool') {
            return TernaryFilter::make($name)->label($label);
        }

        if (str_contains($name, '.')) {
            [$relation, $attr] = explode('.', $name, 2);

            return SelectFilter::make($relation)
                ->label($label)
                ->relationship($relation, $attr)
                ->searchable()
                ->preload();
        }

        $model = static::getModel();
        $map = $f['map'] ?? [];
        $options = is_array($f['filter'])
            ? $f['filter']
            : fn () => $model::query()
                ->whereNotNull($name)
                ->where($name, '!=', '')
                ->distinct()
                ->orderBy($name)
                ->limit(300)
                ->pluck($name)
                ->mapWithKeys(fn ($v) => [$v => $map[$v] ?? $v])
                ->all();

        return SelectFilter::make($name)
            ->label($label)
            ->options($options)
            ->searchable()
            ->multiple();
    }

    // ------------------------------------------------------------
    // صفحة العرض
    // ------------------------------------------------------------
    public static function infolist(Schema $schema): Schema
    {
        $entries = [];
        $wide = [];

        foreach (static::fields() as $name => $f) {
            $type = $f['type'] ?? 'text';
            $label = $f['label'] ?? $name;

            if ($type === 'bool') {
                $entries[] = IconEntry::make($name)->label($label)->boolean();

                continue;
            }

            if ($type === 'json') {
                $wide[] = TextEntry::make($name)
                    ->label($label)
                    ->state(fn (Model $record) => static::flattenJson($record->getAttribute($name)))
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder('—')
                    ->columnSpanFull();

                continue;
            }

            $entry = TextEntry::make($name)->label($label)->placeholder('—');

            match ($type) {
                'date' => $entry->date('Y-m-d'),
                'datetime' => $entry->dateTime('Y-m-d H:i'),
                'number' => $entry->numeric(),
                'badge' => $entry->badge(),
                'long' => $entry->columnSpanFull()->prose(),
                default => null,
            };

            if (! empty($f['map'])) {
                $map = $f['map'];
                $entry->formatStateUsing(fn ($state) => $map[$state] ?? $state);
            }

            if (! empty($f['colors'])) {
                $colors = $f['colors'];
                $entry->color(fn ($state) => $colors[$state] ?? 'gray');
            }

            if ($type === 'long') {
                $wide[] = $entry;
            } else {
                $entries[] = $entry;
            }
        }

        return $schema->components([
            Section::make('البيانات')->schema($entries)->columns(3)->columnSpanFull(),
            ...($wide ? [Section::make('تفاصيل إضافية')->schema($wide)->columnSpanFull()->collapsible()] : []),
        ]);
    }

    /** @return list<string> */
    protected static function flattenJson(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        if (! is_array($value)) {
            return $value === null || $value === '' ? [] : [(string) $value];
        }

        $out = [];
        foreach ($value as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $v = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            $out[] = is_int($k) ? $v : "{$k}: {$v}";
        }

        return $out;
    }
}
