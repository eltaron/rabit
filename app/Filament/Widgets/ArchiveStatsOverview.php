<?php

namespace App\Filament\Widgets;

use App\Models\FacilityData;
use App\Models\FormSubmission;
use App\Models\FormSubmissionData;
use App\Models\FormTemplate;
use App\Models\MaintenancePlan;
use App\Models\OrgUnit;
use App\Models\Report;
use App\Models\SectorData;
use App\Models\StrategicData;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArchiveStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'نظرة عامة على البيانات المؤرشفة';

    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        $vehicles = Vehicle::count();
        $working = Vehicle::where('status', 'صالح')->count();
        $broken = Vehicle::whereIn('status', ['عاطل', 'جسيم'])->count();

        $facilities = FacilityData::count();
        $sector = SectorData::count();
        $strategic = StrategicData::count();
        $maintenance = MaintenancePlan::count();

        $submissions = FormSubmission::count();
        $approved = FormSubmission::whereIn('status', ['approved', 'report_approved', 'report_sent'])->count();

        $total = $vehicles + $facilities + $sector + $strategic + $maintenance + $submissions;

        $pct = fn (int $a, int $b) => $b > 0 ? round($a / $b * 100, 1) : 0;

        return [
            Stat::make('إجمالي السجلات المؤرشفة', number_format($total))
                ->description('كل البيانات المستوردة والمدخلة')
                ->descriptionIcon('heroicon-m-archive-box')
                ->chart($this->monthlyTrend())
                ->color('primary'),

            Stat::make('المركبات', number_format($vehicles))
                ->description("صالح {$pct($working, $vehicles)}% · عاطل {$broken}")
                ->descriptionIcon('heroicon-m-truck')
                ->color($pct($working, $vehicles) >= 60 ? 'success' : 'warning'),

            Stat::make('المنشآت والمرافق', number_format($facilities))
                ->description(FacilityData::distinct('module_code')->count('module_code').' نوع منشأة')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('البيانات القطاعية', number_format($sector))
                ->description(SectorData::distinct('sector')->count('sector').' قطاعات')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            Stat::make('البيانات الاستراتيجية', number_format($strategic))
                ->description(StrategicData::distinct('category')->count('category').' فئة')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('gold'),

            Stat::make('خطط الصيانة', number_format($maintenance))
                ->description('سجل خطة صيانة')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('gray'),

            Stat::make('إرسالات النماذج', number_format($submissions))
                ->description("معتمد {$approved} من {$submissions} · ".FormTemplate::count().' نموذج')
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color('success'),

            Stat::make('قيم البيانات المدخلة', number_format(FormSubmissionData::count()))
                ->description(Report::count().' تقرير · '.number_format(OrgUnit::count()).' وحدة · '.User::count().' مستخدم')
                ->descriptionIcon('heroicon-m-table-cells')
                ->color('gold'),
        ];
    }

    /** عدد السجلات المؤرشفة شهرياً لآخر 12 شهراً */
    private function monthlyTrend(): array
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));

        return $months->map(function ($start) {
            $end = $start->copy()->endOfMonth();

            return collect([Vehicle::class, FacilityData::class, SectorData::class, StrategicData::class, MaintenancePlan::class, FormSubmission::class])
                ->sum(fn ($m) => $m::whereBetween('created_at', [$start, $end])->count());
        })->all();
    }
}
