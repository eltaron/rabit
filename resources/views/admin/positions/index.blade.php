@extends('layouts.app')

@section('title', 'الهيكل الوظيفي')
@section('page-title', 'الهيكل الوظيفي')
@section('page-subtitle', $unit->name . ' — ' . $unit->type_label)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.positions.index') }}">الهيكل الوظيفي</a></li>
    @foreach ($ancestors as $a)
        @if (isset($unitOptions[$a->id]))
            <li class="breadcrumb-item"><a href="{{ route('admin.positions.index', ['unit' => $a->id]) }}">{{ $a->name }}</a></li>
        @endif
    @endforeach
    <li class="breadcrumb-item active">{{ $unit->name }}</li>
@endsection

@section('page-actions')
    <button class="btn btn-kfs-primary" onclick="openPosition()"><i class="fas fa-plus ml-1"></i>وظيفة جديدة</button>
    <div class="dropdown">
        <button class="btn btn-kfs-gold dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-file-excel ml-1"></i>Excel</button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('admin.positions.template') }}"><i class="fas fa-download ml-2 text-gold"></i>تحميل قالب فارغ</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.positions.export', ['unit' => $unit->id]) }}"><i class="fas fa-file-export ml-2 text-gold"></i>تصدير هذه الجهة وفروعها</a></li>
            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-upload ml-2 text-gold"></i>رفع ملف (إضافة / تعديل)</button></li>
        </ul>
    </div>
@endsection

@push('styles')
<style>
    .unit-list { max-height: 62vh; overflow: auto; }
    .unit-list a { display: flex; justify-content: space-between; gap: .5rem; padding: .35rem .6rem; border-radius: var(--r); color: var(--charcoal); font-size: .83rem; white-space: nowrap; }
    .unit-list a:hover { background: var(--papyrus); }
    .unit-list a.active { background: var(--egypt-gold-soft); color: var(--egypt-green); font-weight: 700; }
    .unit-list .cnt { font-family: var(--ff-en); font-size: .7rem; color: #7a5f2c; }

    /* Org chart */
    .org-chart { overflow-x: auto; padding: 1rem .5rem 1.5rem; }
    .org-chart ul { display: flex; justify-content: center; padding-top: 22px; position: relative; margin: 0; padding-inline: 0; }
    .org-chart > ul { padding-top: 0; }
    .org-chart li { list-style: none; position: relative; padding: 22px 8px 0; text-align: center; }
    .org-chart li::before, .org-chart li::after { content: ""; position: absolute; top: 0; width: 50%; height: 22px; border-top: 1px solid var(--egypt-gold); }
    .org-chart li::before { right: 50%; }
    .org-chart li::after { left: 50%; border-right: 1px solid var(--egypt-gold); }
    .org-chart li:only-child::before, .org-chart li:only-child::after { display: none; }
    .org-chart li:only-child { padding-top: 0; }
    .org-chart li:first-child::before, .org-chart li:last-child::after { border: 0 none; }
    .org-chart li:last-child::before { border-left: 1px solid var(--egypt-gold); }
    .org-chart li:first-child::after { border-radius: 0; }
    .org-chart ul ul::before { content: ""; position: absolute; top: 0; left: 50%; border-left: 1px solid var(--egypt-gold); height: 22px; }
    .org-chart > ul > li { padding-top: 0; }
    .org-chart > ul > li::before, .org-chart > ul > li::after { display: none; }

    .pos-card { display: inline-block; min-width: 190px; max-width: 230px; background: var(--white-soft); border: 1px solid var(--line); border-top: 3px solid var(--egypt-green); border-radius: 8px; padding: .7rem .8rem .55rem; box-shadow: var(--shadow); text-align: right; transition: box-shadow .4s var(--ease), border-color .4s var(--ease); }
    .pos-card:hover { box-shadow: var(--shadow-lg); border-top-color: var(--egypt-gold); }
    .pos-card.cat-leadership { border-top-color: var(--egypt-gold); }
    .pos-card.vacant { border-style: dashed; border-top-style: solid; background: var(--papyrus); }
    .pos-card .t { font-family: var(--ff-ar); font-weight: 800; color: var(--egypt-green); font-size: .9rem; line-height: 1.4; }
    .pos-card .h { font-size: .8rem; color: var(--charcoal); margin-top: .2rem; }
    .pos-card .h.vac { color: var(--warning); }
    .pos-card .meta { font-size: .68rem; color: var(--text-soft); margin-top: .25rem; }
    .pos-card .ext { font-size: .68rem; color: var(--info); }
    .pos-card .acts { display: flex; gap: .25rem; justify-content: flex-end; margin-top: .45rem; border-top: 1px solid var(--line); padding-top: .4rem; }
    .pos-card .acts button { border: 0; background: transparent; color: var(--text-soft); width: 26px; height: 26px; border-radius: 4px; font-size: .75rem; }
    .pos-card .acts button:hover { background: var(--papyrus); color: var(--egypt-green); }
</style>
@endpush

@section('content')
    <div class="gov-stats mb-4">
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-briefcase"></i></div><div class="gs-num">{{ $stats['total'] }}</div><div class="gs-label">وظائف معرّفة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-user-check"></i></div><div class="gs-num">{{ $stats['filled'] }}</div><div class="gs-label">وظائف مشغولة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-user-clock"></i></div><div class="gs-num" style="color:var(--warning)">{{ $stats['vacant'] }}</div><div class="gs-label">وظائف شاغرة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-chess-king"></i></div><div class="gs-num">{{ $stats['leadership'] }}</div><div class="gs-label">وظائف قيادية</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-layer-group"></i></div><div class="gs-num">{{ $stats['headcount'] }}</div><div class="gs-label">إجمالي الدرجات المعتمدة</div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-3 col-lg-4">
            <div class="card-kfs">
                <div class="card-header"><i class="fas fa-sitemap ml-2"></i>الجهات</div>
                <div class="card-body p-2">
                    <input type="search" class="form-control form-control-sm mb-2" id="unitFilter" placeholder="تصفية الجهات…" aria-label="تصفية الجهات">
                    <div class="unit-list" id="unitList">
                        @foreach ($unitOptions as $id => $label)
                            @php $pc = $positionCounts[$id] ?? null; @endphp
                            <a href="{{ route('admin.positions.index', ['unit' => $id]) }}" class="{{ $id === $unit->id ? 'active' : '' }}" data-name="{{ trim(str_replace('—', '', $label)) }}">
                                <span class="text-truncate">{{ $label }}</span>
                                @if ($pc)<span class="cnt">{{ $pc->c }}@if ($pc->v) · {{ $pc->v }} شاغرة @endif</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8">
            <div class="card-kfs mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-people-group ml-2"></i>الهيكل الوظيفي — {{ $unit->name }}</span>
                    <small class="text-muted">اضغط على أيقونات البطاقة للتعديل أو التسكين</small>
                </div>
                @if ($positions->isEmpty())
                    <div class="empty-state">
                        <span class="eg-icon lg"><i class="fas fa-people-group"></i></span>
                        <h5>لا توجد وظائف لهذه الجهة</h5>
                        <p>ابدأ بإضافة الوظيفة القيادية (رئيس الجهة) ثم الوظائف التابعة لها، أو ارفع ملف Excel.</p>
                        <button class="btn btn-kfs-primary" onclick="openPosition(null, {category:'leadership'})"><i class="fas fa-plus ml-1"></i>إضافة رئيس الجهة</button>
                    </div>
                @else
                    <div class="org-chart">
                        <ul>
                            @foreach ($byParent[0] ?? [] as $p)
                                @include('admin.positions._node', ['p' => $p])
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @if ($positions->isNotEmpty())
                <div class="card-kfs">
                    <div class="card-header"><i class="fas fa-list ml-2"></i>جدول الوظائف</div>
                    <div class="table-responsive">
                        <table class="table table-kfs mb-0">
                            <thead><tr><th>المسمى</th><th>الفئة</th><th>الدرجة</th><th>يتبع</th><th>الشاغل</th><th>الدرجات</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($positions as $p)
                                    <tr>
                                        <td class="fw-bold">{{ $p->title }} @if ($p->code)<small class="text-muted en d-block">{{ $p->code }}</small>@endif</td>
                                        <td><span class="badge {{ $p->category === 'leadership' ? 'badge-gold' : 'badge-green' }}">{{ $p->category_label }}</span></td>
                                        <td>{{ $p->grade ?: '—' }}</td>
                                        <td><small>{{ $p->parent->title ?? '—' }}@if ($p->parent && $p->parent->org_unit_id !== $unit->id)<span class="d-block text-muted">{{ $p->parent->orgUnit->name ?? '' }}</span>@endif</small></td>
                                        <td>@if ($p->holder){{ $p->holder->name }}@else<span class="status-pill status-pending">شاغرة</span>@endif</td>
                                        <td>{{ $p->headcount }}</td>
                                        <td class="text-end text-nowrap">
                                            <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="openPosition({{ json_encode($p->only(['id','title','code','grade','category','parent_id','user_id','headcount','description','sort_order']), JSON_UNESCAPED_UNICODE) }})" aria-label="تعديل"><i class="fas fa-pen"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-icon" onclick="confirmDelete('{{ route('admin.positions.destroy', $p) }}', @js($p->title))" aria-label="حذف"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Add / edit position --}}
    <div class="modal fade" id="positionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" method="POST" id="positionForm" action="{{ route('admin.positions.store') }}">
                @csrf
                <input type="hidden" name="_method" id="pmMethod" value="POST">
                <input type="hidden" name="org_unit_id" value="{{ $unit->id }}">
                <div class="modal-header"><h5 class="modal-title" id="pmTitle">وظيفة جديدة</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-8"><label class="form-label" for="pm_title">المسمى الوظيفي *</label><input class="form-control" id="pm_title" name="title" required maxlength="255"></div>
                    <div class="col-md-4"><label class="form-label" for="pm_code">الكود</label><input class="form-control en" id="pm_code" name="code" maxlength="50"></div>
                    <div class="col-md-4"><label class="form-label" for="pm_category">الفئة *</label>
                        <select class="form-select" id="pm_category" name="category">@foreach (\App\Models\JobPosition::CATEGORIES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label" for="pm_grade">الدرجة</label>
                        <input class="form-control" id="pm_grade" name="grade" list="grades"><datalist id="grades">@foreach (\App\Models\JobPosition::GRADES as $g)<option value="{{ $g }}">@endforeach</datalist></div>
                    <div class="col-md-4"><label class="form-label" for="pm_headcount">عدد الدرجات</label><input type="number" min="1" class="form-control" id="pm_headcount" name="headcount" value="1"></div>
                    <div class="col-md-6"><label class="form-label" for="pm_parent">يتبع وظيفياً</label>
                        <select class="form-select" id="pm_parent" name="parent_id">
                            <option value="">— لا يتبع (أعلى الهيكل) —</option>
                            @foreach ($parentChoices->groupBy('org_unit_id') as $uid => $group)
                                <optgroup label="{{ $group->first()->orgUnit->name ?? '' }}">
                                    @foreach ($group as $pc)<option value="{{ $pc->id }}">{{ $pc->title }}</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select></div>
                    <div class="col-md-6"><label class="form-label" for="pm_user">الشاغل</label>
                        <select class="form-select" id="pm_user" name="user_id">
                            <option value="">— شاغرة —</option>
                            @foreach ($candidates as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                        <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="move_user" value="1" id="pm_move" checked><label class="form-check-label small" for="pm_move">نقل حساب الشاغل إلى هذه الجهة</label></div>
                    </div>
                    <div class="col-md-9"><label class="form-label" for="pm_desc">المهام / الوصف</label><textarea class="form-control" id="pm_desc" name="description" rows="2"></textarea></div>
                    <div class="col-md-3"><label class="form-label" for="pm_sort">الترتيب</label><input type="number" class="form-control" id="pm_sort" name="sort_order" value="0"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-kfs-primary"><i class="fas fa-check ml-1"></i>حفظ</button></div>
            </form>
        </div>
    </div>

    {{-- Assign --}}
    <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" id="assignForm">
                @csrf
                <div class="modal-header"><h5 class="modal-title">تسكين الوظيفة: <span id="amTitle"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                <div class="modal-body">
                    <label class="form-label" for="am_user">الموظف</label>
                    <select class="form-select" id="am_user" name="user_id">
                        <option value="">— إخلاء الوظيفة —</option>
                        @foreach ($candidates as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="move_user" value="1" id="am_move" checked><label class="form-check-label small" for="am_move">نقل حساب الموظف إلى جهة الوظيفة</label></div>
                    <div class="form-text">الموظف يشغل وظيفة رئيسية واحدة؛ سيتم إخلاء وظيفته السابقة تلقائياً.</div>
                </div>
                <div class="modal-footer"><button class="btn btn-kfs-primary"><i class="fas fa-user-check ml-1"></i>تأكيد</button></div>
            </form>
        </div>
    </div>

    {{-- Import --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('admin.positions.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">رفع ملف الهيكل الوظيفي</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                <div class="modal-body">
                    <label class="drop-zone d-block">
                        <i class="fas fa-file-arrow-up fa-2x text-gold mb-2"></i>
                        <div class="fw-bold text-green">اسحب الملف هنا أو اضغط للاختيار</div>
                        <div class="small text-muted">الصفوف التي بها رقم وظيفة تُعدَّل، والباقي يُضاف</div>
                        <div class="dz-name small mt-2 text-green fw-bold"></div>
                        <input type="file" name="file" accept=".xlsx,.xls" class="d-none" required>
                    </label>
                </div>
                <div class="modal-footer"><a href="{{ route('admin.positions.template') }}" class="btn btn-outline-navy">القالب</a><button class="btn btn-kfs-primary">رفع ومعالجة</button></div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const storeUrl = @json(route('admin.positions.store'));
    const baseUrl = @json(url('admin/positions'));
    function openPosition(p, defaults) {
        p = p || {};
        const d = Object.assign({category: 'executive', headcount: 1, sort_order: 0}, defaults || {}, p);
        const f = document.getElementById('positionForm');
        f.action = p.id ? baseUrl + '/' + p.id : storeUrl;
        document.getElementById('pmMethod').value = p.id ? 'PUT' : 'POST';
        document.getElementById('pmTitle').textContent = p.id ? 'تعديل وظيفة' : 'وظيفة جديدة';
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };
        set('pm_title', d.title); set('pm_code', d.code); set('pm_category', d.category); set('pm_grade', d.grade);
        set('pm_headcount', d.headcount); set('pm_parent', d.parent_id); set('pm_user', d.user_id); set('pm_desc', d.description); set('pm_sort', d.sort_order);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('positionModal')).show();
    }
    function openAssign(id, title, userId) {
        document.getElementById('assignForm').action = baseUrl + '/' + id + '/assign';
        document.getElementById('amTitle').textContent = title;
        document.getElementById('am_user').value = userId || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('assignModal')).show();
    }
    document.getElementById('unitFilter').addEventListener('input', e => {
        const q = e.target.value.trim();
        document.querySelectorAll('#unitList a').forEach(a => a.classList.toggle('d-none', q && !a.dataset.name.includes(q)));
    });
    document.querySelector('#unitList a.active')?.scrollIntoView({block: 'center'});
</script>
@endpush
