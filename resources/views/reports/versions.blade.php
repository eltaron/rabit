@extends('layouts.app')

@section('title', 'سجل الإصدارات — ' . $report->title)
@section('page-title', 'سجل الإصدارات')
@section('page-subtitle', $report->title)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.show', $report) }}">{{ \Illuminate\Support\Str::limit($report->title, 40) }}</a></li>
    <li class="breadcrumb-item active">الإصدارات</li>
@endsection
@section('page-actions')
    @can('manage_reports')
        <a href="{{ route('reports.edit', $report) }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>إصدار جديد</a>
    @endcan
    <a href="{{ route('reports.show', $report) }}" class="btn btn-kfs-gold"><i class="fas fa-arrow-right ml-1"></i>العودة للتقرير</a>
@endsection

@push('styles')
<style>
    .version-btn { text-align:right; border-color:var(--line) !important; }
    .version-btn.active { background:var(--papyrus) !important; color:var(--charcoal) !important; border-right:3px solid var(--egypt-gold) !important; }
    .v-content { min-height:150px; line-height:2.1; background:var(--papyrus); border:1px solid var(--line); border-radius:var(--r); padding:1.25rem 1.5rem; white-space:pre-line; }
    .v-ai { line-height:2.1; border:1px solid var(--line-gold); border-right:3px solid var(--egypt-gold); border-radius:var(--r); padding:1rem 1.25rem; white-space:pre-line; }
</style>
@endpush

@section('content')
@if (isset($versionA, $versionB))
    <div class="card-kfs mb-4">
        <div class="card-header"><i class="fas fa-code-compare ml-2"></i>مقارنة الإصدارين <span class="en">v{{ $versionA->version_number }}</span> و <span class="en">v{{ $versionB->version_number }}</span></div>
        <div class="card-body row g-3">
            @foreach ([$versionA, $versionB] as $v)
                <div class="col-md-6">
                    <h6 class="mb-2"><span class="badge badge-gold en">v{{ $v->version_number }}</span> <small class="text-muted">{{ $v->created_at->format('Y/m/d H:i') }}</small></h6>
                    <div class="v-content">{{ $v->content ?: 'لا يوجد محتوى.' }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-kfs">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-code-branch ml-2"></i>الإصدارات</span>
                <span class="badge badge-gold">{{ $versions->total() }}</span>
            </div>
            <div class="list-group list-group-flush" style="max-height:600px;overflow-y:auto">
                @forelse ($versions as $version)
                    <button type="button" class="list-group-item list-group-item-action version-btn"
                            data-version="{{ $version->version_number }}" data-version-id="{{ $version->id }}"
                            data-url="{{ route('reports.versions.show', [$report, $version]) }}"
                            data-notes="{{ $version->change_notes ?? '' }}" data-created="{{ $version->created_at->format('Y/m/d H:i') }}"
                            data-author="{{ $version->createdBy->name ?? '' }}" onclick="loadVersion(this)">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge {{ $version->is_current ? 'badge-gold' : 'badge-green' }} en">v{{ $version->version_number }}@if ($version->is_current) · الحالي @endif</span>
                            <small class="text-muted">{{ $version->created_at->format('Y/m/d') }}</small>
                        </div>
                        @if ($version->change_notes)
                            <small class="text-muted d-block mt-1 text-truncate"><i class="fas fa-comment-dots ml-1"></i>{{ $version->change_notes }}</small>
                        @endif
                    </button>
                @empty
                    <div class="empty-state py-4"><span class="eg-icon"><i class="fas fa-code-branch"></i></span><p class="small mb-0">لا توجد إصدارات</p></div>
                @endforelse
            </div>
            @if ($versions->hasPages())<div class="card-footer">{{ $versions->links() }}</div>@endif
        </div>

        @if ($versions->count() > 1)
            <form method="POST" action="{{ route('reports.versions.compare', $report) }}" class="card-kfs mt-3">
                @csrf
                <div class="card-header"><i class="fas fa-code-compare ml-2"></i>مقارنة إصدارين</div>
                <div class="card-body row g-2">
                    <div class="col-6">
                        <select name="version_a" class="form-select form-select-sm" aria-label="الإصدار الأول">
                            @foreach ($versions as $v)<option value="{{ $v->id }}">v{{ $v->version_number }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <select name="version_b" class="form-select form-select-sm" aria-label="الإصدار الثاني">
                            @foreach ($versions as $v)<option value="{{ $v->id }}" @selected($loop->index === 1)>v{{ $v->version_number }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12"><button class="btn btn-sm btn-kfs-gold w-100">مقارنة</button></div>
                </div>
            </form>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="card-kfs mb-3">
            <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <strong id="versionTitle" style="color:var(--egypt-green)">الإصدار الحالي</strong>
                    <span class="text-muted small mr-2" id="versionMeta"></span>
                </div>
                <div class="d-flex gap-2">
                    @can('manage_reports')
                        <form id="revertForm" method="POST" action="" class="d-none" onsubmit="return confirm('استعادة هذا الإصدار كإصدار جديد؟')">
                            @csrf
                            <button class="btn btn-sm btn-kfs-gold"><i class="fas fa-rotate-left ml-1"></i>استعادة هذا الإصدار</button>
                        </form>
                    @endcan
                    @if ($report->formSubmission)
                        <a href="{{ route('submissions.show', $report->formSubmission) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-up-right-from-square ml-1"></i>الإرسال المصدر</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-kfs mb-3">
            <div class="card-header"><i class="fas fa-align-right ml-2"></i>محتوى الإصدار</div>
            <div class="card-body">
                <div id="versionContent" class="v-content">{{ $report->content ?: 'لا يوجد محتوى مكتوب لهذا الإصدار.' }}</div>
                <div id="versionAiContent" class="mt-3" @if (! $report->ai_generated_content) style="display:none" @endif>
                    <h6 class="small fw-bold"><i class="fas fa-wand-magic-sparkles ml-1 text-gold"></i>الملخص المولّد بالذكاء الاصطناعي</h6>
                    <div class="v-ai" id="versionAiContentBody">{{ $report->ai_generated_content }}</div>
                </div>
            </div>
        </div>

        @if ($rows->count() && count($headers))
            <div class="card-kfs">
                <div class="card-header"><i class="fas fa-table ml-2"></i>بيانات الإرسال <small class="text-muted">({{ $rows->count() }} صف / {{ count($headers) }} عمود)</small></div>
                <div class="table-responsive" style="max-height:480px">
                    <table class="table table-kfs mb-0" style="font-size:.82rem">
                        <thead><tr><th style="width:40px">#</th>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                <tr><td class="text-muted">{{ $i + 1 }}</td>@foreach ($headers as $h)<td>{{ $row[$h] ?? '—' }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    const revertBase = @json(route('reports.versions.revert', [$report, '__ID__']));
    function loadVersion(btn) {
        document.querySelectorAll('.version-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const d = btn.dataset;
        document.getElementById('versionTitle').textContent = 'الإصدار v' + d.version;
        document.getElementById('versionMeta').textContent = d.created + ' — ' + d.author + (d.notes ? ' — ' + d.notes : '');
        const contentEl = document.getElementById('versionContent');
        const aiWrap = document.getElementById('versionAiContent');
        const aiBody = document.getElementById('versionAiContentBody');
        contentEl.textContent = 'جاري التحميل…';
        const rf = document.getElementById('revertForm');
        if (rf) { rf.action = revertBase.replace('__ID__', d.versionId); rf.classList.remove('d-none'); }
        fetch(d.url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                contentEl.textContent = data.content || 'لا يوجد محتوى مكتوب لهذا الإصدار.';
                aiWrap.style.display = data.ai_generated_content ? 'block' : 'none';
                aiBody.textContent = data.ai_generated_content || '';
            })
            .catch(() => { contentEl.textContent = 'تعذر تحميل الإصدار.'; });
    }
</script>
@endpush
