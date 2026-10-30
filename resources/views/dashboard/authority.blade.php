@extends('layouts.app')

@section('title', 'لوحة السلطة المختصة')
@section('page-title', 'لوحة السلطة المختصة')
@section('page-subtitle', 'متابعة التقارير المعتمدة وإرسالها للجهات المعنية')
@section('breadcrumb')
    <li class="breadcrumb-item active">لوحة التحكم</li>
@endsection
@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-kfs-primary"><i class="fas fa-chart-column ml-1"></i>التقارير</a>
@endsection

@section('content')
    @include('dashboard.partials._styles')
    @include('dashboard.partials._greeting')

    @include('dashboard.partials._stats', ['items' => [
        ['fa-chart-column', 'إجمالي التقارير', $reportStats['total'], route('reports.index')],
        ['fa-hourglass-half', 'بانتظار الاعتماد', $reportStats['awaiting'], route('reports.index', ['status' => 'pending_approval'])],
        ['fa-circle-check', 'معتمدة', $reportStats['approved'], route('reports.index', ['status' => 'approved'])],
        ['fa-paper-plane', 'مُرسلة', $reportStats['sent'], route('reports.index', ['status' => 'sent'])],
        ['fa-magnifying-glass', 'إرسالات قيد المراجعة', $counts['review'], route('submissions.index', ['tab' => 'in_review'])],
    ]])

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card-kfs h-100" data-reveal>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list-check ml-2"></i>تقارير تحتاج إجراء</span>
                    <a href="{{ route('reports.index') }}" class="small fw-bold">عرض الكل <i class="fas fa-arrow-left mr-1 text-gold"></i></a>
                </div>
                @if ($reportsQueue->isEmpty())
                    <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-check-double"></i></span><p class="mb-0 small">لا توجد تقارير تحتاج إجراء.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-kfs mb-0">
                            <thead><tr><th>التقرير</th><th>المنشئ</th><th>الحالة</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($reportsQueue as $r)
                                    <tr>
                                        <td><a href="{{ route('reports.show', $r) }}" class="fw-bold">{{ \Illuminate\Support\Str::limit($r->title, 50) }}</a><span class="sub">{{ $r->created_at?->format('Y/m/d') }}</span></td>
                                        <td class="small">{{ $r->createdBy->name ?? '—' }}</td>
                                        <td>@include('dashboard.partials._status', ['status' => $r->status])</td>
                                        <td><a href="{{ route('reports.show', $r) }}" class="btn btn-sm btn-kfs-gold">فتح</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-xl-5">
            @php
                $tot = max(1, $reportStats['total']);
                $rs = [
                    ['label' => 'مسودة', 'class' => 'draft', 'value' => $reportStats['draft']],
                    ['label' => 'بانتظار الاعتماد', 'class' => 'pending', 'value' => $reportStats['awaiting']],
                    ['label' => 'معتمد', 'class' => 'success', 'value' => $reportStats['approved']],
                    ['label' => 'مُرسل', 'class' => 'info', 'value' => $reportStats['sent']],
                    ['label' => 'مرفوض', 'class' => 'error', 'value' => $reportStats['rejected']],
                ];
            @endphp
            <div class="card-kfs h-100" data-reveal>
                <div class="card-header"><i class="fas fa-chart-bar ml-2"></i>حالة التقارير</div>
                <div class="card-body">
                    @foreach ($rs as $r)
                        <div class="hbar">
                            <span class="lbl"><span class="badge-status {{ $r['class'] === 'draft' ? 'badge-draft' : 'status-' . $r['class'] }}" style="padding:.1rem .4rem">{{ $r['label'] }}</span></span>
                            <div class="track"><div class="fill {{ $r['class'] === 'draft' ? '' : $r['class'] }}" style="width: {{ $r['value'] / $tot * 100 }}%; {{ $r['class'] === 'draft' ? 'background:var(--muted)' : '' }}"></div></div>
                            <span class="val">{{ number_format($r['value']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('dashboard.partials._charts', ['monthly' => $reportsMonthly, 'trendTitle' => 'التقارير المنشأة خلال آخر 6 أشهر'])

    @include('dashboard.partials._side')
@endsection
