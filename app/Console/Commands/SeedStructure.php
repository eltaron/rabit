<?php

namespace App\Console\Commands;

use App\Models\OrgUnit;
use App\Services\OrgStructureService;
use Illuminate\Console\Command;

class SeedStructure extends Command
{
    protected $signature = 'kfs:seed-structure {--file= : مسار ملف التقسيم الإداري} {--skip-admin} {--skip-entities} {--with-positions : إنشاء الوظائف القيادية الأساسية}';

    protected $description = 'بناء الهيكل التنظيمي للمحافظة (التقسيم الإداري + المديريات والهيئات) — آمن للتكرار';

    public function handle(OrgStructureService $svc): int
    {
        if (! OrgUnit::root()) {
            $this->error('لا توجد جهة جذرية (المحافظة). شغّل الترحيلات أولاً.');
            return self::FAILURE;
        }

        if (! $this->option('skip-entities')) {
            $this->info('المديريات والهيئات وديوان عام المحافظة…');
            $svc->seedEntities();
        }
        if (! $this->option('skip-admin')) {
            $this->info('التقسيم الإداري (مدن / أحياء / وحدات محلية / قرى / عزب)…');
            $svc->importAdministrative($this->option('file') ?: null);
        }

        if ($this->option('with-positions')) {
            $this->info('الوظائف القيادية الأساسية…');
            $svc->seedPositions();
        }

        $rows = collect($svc->stats())->map(fn ($n, $t) => [OrgUnit::TYPES[$t] ?? ($t === 'positions' ? 'وظائف' : $t), $n])->values()->all();
        $this->table(['النوع', 'المضاف'], $rows ?: [['—', 0]]);
        $this->info('إجمالي الجهات الآن: ' . OrgUnit::count());

        return self::SUCCESS;
    }
}
