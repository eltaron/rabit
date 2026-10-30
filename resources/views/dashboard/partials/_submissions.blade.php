{{-- جدول إرسالات: $rows, $title, $icon, $empty, $more --}}
<div class="card-kfs h-100" data-reveal>
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas {{ $icon ?? 'fa-inbox' }} ml-2"></i>{{ $title }}</span>
        @if (! empty($more))<a href="{{ $more }}" class="small fw-bold">عرض الكل <i class="fas fa-arrow-left mr-1 text-gold"></i></a>@endif
    </div>
    @if ($rows->isEmpty())
        <div class="empty-state py-4">
            <span class="eg-icon"><i class="far fa-folder-open"></i></span>
            <p class="mb-0 small">{{ $empty ?? 'لا توجد بيانات حالياً.' }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-kfs mb-0">
                <thead><tr><th>المرجع</th><th>النموذج</th><th class="d-none d-md-table-cell">الجهة</th><th>الحالة</th><th class="d-none d-sm-table-cell">التاريخ</th></tr></thead>
                <tbody>
                    @foreach ($rows as $s)
                        <tr>
                            <td><a href="{{ route('submissions.show', $s) }}" class="fw-bold en">{{ $s->reference_number }}</a></td>
                            <td>{{ \Illuminate\Support\Str::limit($s->formTemplate->name ?? '—', 40) }}<span class="sub">{{ $s->createdBy->name ?? '' }}</span></td>
                            <td class="d-none d-md-table-cell small">{{ $s->orgUnit->name ?? '—' }}</td>
                            <td>@include('dashboard.partials._status', ['status' => $s->status])</td>
                            <td class="d-none d-sm-table-cell small text-muted">{{ $s->created_at?->format('Y/m/d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
