<?php

namespace App\Console\Commands;

use App\Services\MapDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * توحيد الإحداثيات: يحوّل X/Y (بترتيب غير موثوق) إلى latitude/longitude داخل حدود المحافظة.
 */
class GeoNormalize extends Command
{
    protected $signature = 'kfs:geo-normalize {--force : إعادة حساب كل الصفوف وليس الفارغة فقط}';

    protected $description = 'توحيد إحداثيات البيانات المكانية (المرافق والقطاعات) والتحقق من وقوعها داخل حدود محافظة كفر الشيخ';

    public function handle(): int
    {
        Cache::forget('map.boundary.v1');
        if (! MapDataService::boundary()) {
            $this->error('ملف حدود المحافظة public/data/kafr-el-sheikh.geojson غير موجود أو غير صالح.');
            return self::FAILURE;
        }

        $rows = [];
        foreach (['facilities_data' => 'المرافق والمنشآت', 'sector_data' => 'البيانات القطاعية'] as $table => $label) {
            $s = MapDataService::normalizeTable($table, (bool) $this->option('force'));
            $rows[] = [$label, $s['checked'], $s['located'], $s['rejected'], $s['linked'], $s['nearest']];
        }

        $this->table(['الجدول', 'تم فحصه', 'إحداثيات صالحة', 'مرفوض (خارج الحدود)', 'رُبط بمركزه', 'نُسب لأقرب مركز'], $rows);
        return self::SUCCESS;
    }
}
