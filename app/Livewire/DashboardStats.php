<?php

namespace App\Livewire;

use App\Models\FormSubmission;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DashboardStats extends Component
{
    public array $stats = [
        'total_submissions' => 0,
        'pending_review' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total_reports' => 0,
        'active_users' => 0,
        'revision_required' => 0,
        'completed_today' => 0,
    ];

    protected static string $poll = '30s';

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        // نطاق بيانات المستخدم حسب جهته في الهيكل
        $query = \App\Support\DataScope::submissions(FormSubmission::query(), $user);

        $this->stats['total_submissions'] = (clone $query)->count();
        $this->stats['pending_review'] = (clone $query)->where('status', 'under_review')->count();
        $this->stats['approved'] = (clone $query)->where('status', 'approved')->count();
        $this->stats['rejected'] = (clone $query)->where('status', 'rejected')->count();
        $this->stats['revision_required'] = (clone $query)->where('status', 'revision_required')->count();
        $this->stats['completed_today'] = (clone $query)
            ->where('status', 'approved')
            ->whereDate('approved_at', today())
            ->count();

        if (\App\Support\DataScope::level($user) === 'all') {
            $this->stats['total_reports'] = Report::count();
            $this->stats['active_users'] = User::where('is_active', true)
                ->whereDate('updated_at', '>=', now()->subDays(7))
                ->count();
        } else {
            $this->stats['total_reports'] = Report::where('created_by', $user->id)->count();
            $this->stats['active_users'] = 0;
        }

        $this->dispatch('statsRefreshed');
    }

    public function render()
    {
        return view('livewire.dashboard-stats');
    }
}
