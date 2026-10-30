<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class KpiCards extends Component
{
    public array $kpiData = [
        'total_submissions' => 0,
        'approval_rate' => 0,
        'avg_processing_days' => 0,
        'top_department' => '',
        'department_stats' => [],
        'monthly_trend' => [],
        'revision_rate' => 0,
        'total_reports_generated' => 0,
    ];

    protected static string $poll = '60s';

    public function mount(): void
    {
        $this->loadKpiData();
    }

    public function loadKpiData(): void
    {
        $totalSubmissions = FormSubmission::count();
        $approvedCount = FormSubmission::where('status', 'approved')->count();
        $revisionCount = FormSubmission::where('status', 'revision_required')->count();

        $this->kpiData['total_submissions'] = $totalSubmissions;
        $this->kpiData['approval_rate'] = $totalSubmissions > 0
            ? round(($approvedCount / $totalSubmissions) * 100, 1)
            : 0;
        $this->kpiData['revision_rate'] = $totalSubmissions > 0
            ? round(($revisionCount / $totalSubmissions) * 100, 1)
            : 0;

        $avgDays = FormSubmission::whereNotNull('approved_at')
            ->whereNotNull('submitted_at')
            ->selectRaw('AVG(DATEDIFF(approved_at, submitted_at)) as avg_days')
            ->value('avg_days');
        $this->kpiData['avg_processing_days'] = round($avgDays ?? 0, 1);

        $topDept = Department::select('departments.id', 'departments.name', DB::raw('COUNT(form_submissions.id) as submission_count'))
            ->join('users', 'users.department_id', '=', 'departments.id')
            ->join('form_submissions', 'form_submissions.created_by', '=', 'users.id')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('submission_count')
            ->first();
        $this->kpiData['top_department'] = $topDept?->name ?? '-';

        $this->kpiData['department_stats'] = Department::select('departments.id', 'departments.name', DB::raw('COUNT(form_submissions.id) as count'))
            ->join('users', 'users.department_id', '=', 'departments.id')
            ->join('form_submissions', 'form_submissions.created_by', '=', 'users.id')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();

        $this->kpiData['monthly_trend'] = FormSubmission::select(
            DB::raw('MONTH(submitted_at) as month'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved')
        )
            ->whereYear('submitted_at', date('Y'))
            ->whereNotNull('submitted_at')
            ->groupBy(DB::raw('MONTH(submitted_at)'))
            ->orderBy('month')
            ->get()
            ->toArray();

        $this->kpiData['total_reports_generated'] = Report::count();

        $this->dispatch('kpiRefreshed');
    }

    public function render()
    {
        return view('livewire.kpi-cards');
    }
}
