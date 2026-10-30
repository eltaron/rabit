@extends('layouts.app')

@section('title', 'مركز ملفات Excel')
@section('page-title', 'مركز ملفات Excel')
@section('page-subtitle', 'حمّل قالب أي نموذج، املأه بالبيانات، ثم أعد رفعه للإضافة أو التعديل')
@section('breadcrumb')
    <li class="breadcrumb-item active">مركز ملفات Excel</li>
@endsection

@push('styles')
<style>
    .xl-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; }
    .xl-step { padding: 1.2rem 1.4rem; position: relative; display: flex; gap: .9rem; align-items: flex-start; }
    .xl-step + .xl-step::before { content: ""; position: absolute; right: 0; top: 20%; bottom: 20%; width: 1px; background: var(--egypt-gold-line); }
    .xl-step .num { font-family: var(--ff-num); color: var(--egypt-gold); font-size: 1.6rem; line-height: 1; font-weight: 700; }
    .xl-step h3 { font-size: 1rem; margin: 0 0 .2rem; }
    .xl-step p { margin: 0; color: var(--text-soft); font-size: .84rem; }
    .tpl-card .tpl-meta { display: flex; gap: .75rem; flex-wrap: wrap; font-size: .76rem; color: var(--text-soft); }
    .tpl-card .tpl-actions { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .4rem; }
    @media (max-width: 767.98px) {
        .xl-steps { grid-template-columns: 1fr; }
        .xl-step + .xl-step::before { right: 20%; left: 20%; top: 0; bottom: auto; width: auto; height: 1px; }
    }
</style>
@endpush

@section('content')
    @include('excel._import-errors')

    {{-- الخطوات الثلاث --}}
    <div class="card-kfs mb-4" data-reveal>
        <div class="xl-steps">
            <div class="xl-step">
                <span class="num">01</span>
                <div>
                    <h3>حمّل القالب</h3>
                    <p>اختر النموذج واضغط «تحميل القالب». الملف رسمي ومجهّز بالأعمدة وقوائم الاختيار.</p>
                </div>
            </div>
            <div class="xl-step">
                <span class="num">02</span>
                <div>
                    <h3>املأ البيانات</h3>
                    <p>أدخل صفاً لكل سجل بدءاً من الصف الخامس. الأعمدة المعلّمة بـ * إلزامية. لا تعدّل صف العناوين.</p>
                </div>
            </div>
            <div class="xl-step">
                <span class="num">03</span>
                <div>
                    <h3>ارفع الملف</h3>
                    <p>ارفع الملف كإرسال جديد، أو صدّر إرسالاً قائماً وعدّله ثم ارفعه بوضع «تحديث».</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="section-head d-flex justify-content-between align-items-end flex-wrap gap-2">
                <div>
                    <div class="sh-line"><span class="sh-num">01</span></div>
                    <h2>النماذج المتاحة لجهتك</h2>
                    <p>{{ number_format($templates->total()) }} نموذج متاح للتحميل والرفع</p>
                </div>
                <form method="GET" class="top-search" style="width:min(320px,100%)" role="search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="ابحث عن نموذج…" aria-label="بحث في النماذج">
                </form>
            </div>

            @if ($templates->isEmpty())
                <div class="empty-state card-kfs">
                    <span class="eg-icon lg"><i class="far fa-folder-open"></i></span>
                    <h5>لا توجد نماذج متاحة</h5>
                    <p class="mb-0">لم يتم توجيه أي نموذج لجهتك بعد، أو لا توجد نتائج مطابقة للبحث.</p>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($templates as $t)
                        <div class="col-md-6">
                            <div class="service-card tpl-card">
                                <div class="d-flex gap-3 align-items-start">
                                    <span class="eg-icon"><i class="fas fa-file-excel"></i></span>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h5 class="text-truncate" title="{{ $t->name }}">{{ $t->name }}</h5>
                                        <div class="tpl-meta">
                                            <span><i class="fas fa-table-columns ml-1 text-gold"></i>{{ $t->form_fields_count }} عمود</span>
                                            @if ($t->orgUnit)<span><i class="fas fa-building ml-1 text-gold"></i>{{ $t->orgUnit->name }}</span>@endif
                                        </div>
                                    </div>
                                </div>
                                <p>{{ \Illuminate\Support\Str::limit($t->description ?: 'نموذج بيانات', 90) }}</p>
                                <div class="tpl-actions">
                                    <a href="{{ route('excel.template.download', $t) }}" class="btn btn-sm btn-kfs-gold">
                                        <i class="fas fa-download ml-1"></i>تحميل القالب
                                    </a>
                                    @can('create_submissions')
                                        <button type="button" class="btn btn-sm btn-kfs" data-bs-toggle="modal" data-bs-target="#uploadNew"
                                                data-upload-action="{{ route('excel.template.import', $t) }}"
                                                data-upload-title="رفع بيانات: {{ $t->name }}"
                                                data-upload-subtitle="سيتم إنشاء إرسال جديد باسم جهتك يحتوي على صفوف الملف.">
                                            <i class="fas fa-upload ml-1"></i>رفع كإرسال جديد
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">{{ $templates->links() }}</div>
            @endif
        </div>

        <div class="col-xl-4">
            <div class="section-head">
                <div class="sh-line"><span class="sh-num">02</span></div>
                <h2>آخر الإرسالات</h2>
                <p>صدّر البيانات للتعديل ثم أعد رفعها</p>
            </div>
            <div class="card-kfs">
                <div class="list-group list-group-flush">
                    @forelse ($submissions as $s)
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="overflow-hidden">
                                    <a href="{{ route('submissions.show', $s) }}" class="fw-bold en">{{ $s->reference_number }}</a>
                                    <div class="small text-truncate">{{ $s->formTemplate->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $s->rows_count }} صف · {{ $s->orgUnit->name ?? '—' }}</small>
                                </div>
                                @include('partials._status-badge', ['status' => $s->status])
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('excel.submission.export', $s) }}" class="btn btn-sm btn-outline-navy">
                                    <i class="fas fa-file-export ml-1"></i>تصدير
                                </a>
                                @if (\App\Http\Controllers\Form\FormExcelController::canEditData(auth()->user(), $s))
                                    <button type="button" class="btn btn-sm btn-kfs-gold" data-bs-toggle="modal" data-bs-target="#uploadExisting"
                                            data-upload-action="{{ route('excel.submission.import', $s) }}"
                                            data-upload-title="إعادة رفع: {{ $s->reference_number }}"
                                            data-upload-subtitle="{{ $s->formTemplate->name ?? '' }}">
                                        <i class="fas fa-file-import ml-1"></i>إعادة رفع
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <span class="eg-icon"><i class="fas fa-inbox"></i></span>
                            <p class="mb-0 mt-2">لا توجد إرسالات بعد</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @include('excel._upload-modal', ['id' => 'uploadNew', 'action' => '#', 'title' => 'رفع ملف Excel', 'withMode' => false, 'modes' => $modes])
    @include('excel._upload-modal', ['id' => 'uploadExisting', 'action' => '#', 'title' => 'إعادة رفع ملف Excel', 'withMode' => true, 'modes' => $modes])
@endsection
