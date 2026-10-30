@extends('layouts.app')

@section('title', 'الهيكل التنظيمي')
@section('page-title', 'الهيكل التنظيمي لمحافظة كفر الشيخ')
@section('page-subtitle', 'المراكز والمدن والقرى والمديريات والهيئات وكل الجهات التابعة في شجرة واحدة')
@section('breadcrumb')
    <li class="breadcrumb-item active">الهيكل التنظيمي</li>
@endsection

@section('page-actions')
    <a href="{{ route('admin.org-units.create') }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>جهة جديدة</a>
    <div class="dropdown">
        <button class="btn btn-kfs-gold dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-file-excel ml-1"></i>Excel</button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('admin.org-units.template') }}"><i class="fas fa-download ml-2 text-gold"></i>تحميل قالب فارغ</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.org-units.export') }}"><i class="fas fa-file-export ml-2 text-gold"></i>تصدير الهيكل كاملاً</a></li>
            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-upload ml-2 text-gold"></i>رفع ملف (إضافة / تعديل)</button></li>
            <li><hr class="dropdown-divider"></li>
            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#adminModal"><i class="fas fa-map ml-2 text-gold"></i>تحديث التقسيم الإداري</button></li>
        </ul>
    </div>
@endsection

@push('styles')
<style>
    .ou-tree { list-style: none; margin: 0; padding: 0; }
    .ou-tree ul { list-style: none; margin: 0; padding: 0 1.15rem 0 0; border-right: 1px dashed var(--line-gold); margin-right: .55rem; }
    .ou-row { display: flex; align-items: center; gap: .45rem; padding: .32rem .5rem; border-radius: var(--r); cursor: pointer; transition: background .3s var(--ease); }
    .ou-row:hover { background: var(--papyrus); }
    .ou-row.active { background: var(--egypt-gold-soft); box-shadow: inset -2px 0 0 var(--egypt-gold); }
    .ou-toggle { width: 22px; height: 22px; border: 1px solid var(--line); border-radius: 4px; background: var(--white-soft); color: var(--egypt-green); font-size: .65rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ou-toggle.leaf { visibility: hidden; }
    .ou-ico { color: var(--egypt-gold); width: 18px; text-align: center; font-size: .85rem; }
    .ou-name { font-size: .87rem; color: var(--charcoal); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ou-name.inactive { color: var(--muted); text-decoration: line-through; }
    .ou-type { font-size: .68rem; color: var(--text-soft); white-space: nowrap; }
    .ou-count { font-size: .66rem; border: 1px solid var(--line-gold); color: #7a5f2c; border-radius: 4px; padding: 0 .35rem; font-family: var(--ff-en); }
    .tree-box { max-height: 68vh; overflow: auto; }
    .detail-sticky { position: sticky; top: calc(var(--idbar-height) + var(--navbar-height) + 16px); }
    .type-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .6rem; border: 1px solid var(--line); border-radius: var(--r); background: var(--white-soft); font-size: .78rem; color: var(--text); }
    .type-chip i { color: var(--egypt-gold); }
    .type-chip b { color: var(--egypt-green); font-family: var(--ff-en); }
</style>
@endpush

@section('content')
    <div class="gov-stats mb-4">
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-sitemap"></i></div><div class="gs-num" data-count="{{ $total }}">{{ number_format($total) }}</div><div class="gs-label">إجمالي الجهات</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-city"></i></div><div class="gs-num" data-count="{{ ($typeCounts['center'] ?? 0) }}">{{ $typeCounts['center'] ?? 0 }}</div><div class="gs-label">مراكز ومدن</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-house-chimney"></i></div><div class="gs-num" data-count="{{ ($typeCounts['local_unit'] ?? 0) + ($typeCounts['village'] ?? 0) }}">{{ ($typeCounts['local_unit'] ?? 0) + ($typeCounts['village'] ?? 0) }}</div><div class="gs-label">وحدات محلية وقرى</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-building-columns"></i></div><div class="gs-num" data-count="{{ ($typeCounts['ministry'] ?? 0) + ($typeCounts['directorate'] ?? 0) + ($typeCounts['authority'] ?? 0) + ($typeCounts['company'] ?? 0) }}">0</div><div class="gs-label">مديريات وهيئات وشركات</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-users"></i></div><div class="gs-num" data-count="{{ $usersCount }}">{{ $usersCount }}</div><div class="gs-label">مستخدمون مرتبطون</div></div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach (\App\Models\OrgUnit::TYPES as $t => $label)
            @if (($typeCounts[$t] ?? 0) > 0)
                <span class="type-chip"><i class="fas {{ \App\Models\OrgUnit::ICONS[$t] }}"></i>{{ $label }} <b>{{ number_format($typeCounts[$t]) }}</b></span>
            @endif
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-kfs">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <span><i class="fas fa-diagram-project ml-2"></i>شجرة الجهات</span>
                    <div class="top-search" style="width:min(320px,100%);height:36px">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="search" id="treeSearch" placeholder="ابحث عن جهة أو قرية أو كود…" aria-label="بحث في الشجرة">
                    </div>
                </div>
                <div class="card-body tree-box">
                    <div id="searchResults" class="d-none"></div>
                    <ul class="ou-tree" id="tree" role="tree" aria-label="الهيكل التنظيمي"></ul>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="detail-sticky">
                <div class="card-kfs egypt-frame" id="detail">
                    <div class="card-body p-4">
                        <div class="empty-state py-4">
                            <span class="eg-icon lg"><i class="fas fa-hand-pointer"></i></span>
                            <h5>اختر جهة من الشجرة</h5>
                            <p class="mb-0">لعرض بياناتها والجهات التابعة لها والإجراءات المتاحة.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Excel import --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('admin.org-units.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">رفع ملف الهيكل التنظيمي</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                <div class="modal-body">
                    <p class="text-muted small">حمّل القالب أو صدّر الهيكل الحالي، عدّل أو أضف صفوفاً، ثم ارفع الملف. الصفوف التي بها «رقم الجهة» تُعدَّل، والصفوف بدون رقم تُضاف كجهات جديدة.</p>
                    <label class="drop-zone d-block">
                        <i class="fas fa-file-arrow-up fa-2x text-gold mb-2"></i>
                        <div class="fw-bold text-green">اسحب الملف هنا أو اضغط للاختيار</div>
                        <div class="small text-muted">xlsx / xls — حتى 20 ميجابايت</div>
                        <div class="dz-name small mt-2 text-green fw-bold"></div>
                        <input type="file" name="file" accept=".xlsx,.xls" class="d-none" required>
                    </label>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.org-units.template') }}" class="btn btn-outline-navy"><i class="fas fa-download ml-1"></i>القالب</a>
                    <button class="btn btn-kfs-primary"><i class="fas fa-upload ml-1"></i>رفع ومعالجة</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Administrative division --}}
    <div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('admin.org-units.import-administrative') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">تحديث التقسيم الإداري</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">يقرأ بيان وزارة التنمية المحلية (المدن والأحياء والوحدات المحلية القروية والقرى التوابع والعزب والكفور والنجوع) ويضيف الناقص فقط دون تكرار، ويضيف المديريات والهيئات الرئيسية غير الموجودة.</p>
                    <label class="form-label">ملف بديل (اختياري)</label>
                    <input type="file" name="file" accept=".xlsx,.xls" class="form-control">
                    <div class="form-text">اتركه فارغاً لاستخدام آخر بيان محفوظ على الخادم.</div>
                </div>
                <div class="modal-footer"><button class="btn btn-kfs-primary"><i class="fas fa-rotate ml-1"></i>تحديث</button></div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const treeUrl = @json(route('admin.org-units.tree'));
    const showBase = @json(url('admin/org-units'));
    const createUrl = @json(route('admin.org-units.create'));
    const positionsUrl = @json(route('admin.positions.index'));
    const usersUrl = @json(route('admin.users.index'));
    const exportUrl = @json(route('admin.org-units.export'));
    const selected = @json($selected);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmt = n => new Intl.NumberFormat('ar-EG').format(n || 0);
    const loaded = {};

    function li(n) {
        const el = document.createElement('li');
        el.setAttribute('role', 'treeitem');
        el.dataset.id = n.id;
        el.innerHTML = `<div class="ou-row" tabindex="0">
            <button type="button" class="ou-toggle ${n.children ? '' : 'leaf'}" aria-label="توسيع"><i class="fas fa-plus"></i></button>
            <i class="fas ${esc(n.icon)} ou-ico" aria-hidden="true"></i>
            <span class="ou-name ${n.active ? '' : 'inactive'}">${esc(n.name)}</span>
            <span class="ou-type d-none d-md-inline">${esc(n.type_label)}</span>
            ${n.children ? `<span class="ou-count" title="جهات تابعة">${fmt(n.children)}</span>` : ''}
        </div><ul class="d-none"></ul>`;
        el._node = n;
        const row = el.querySelector('.ou-row');
        el.querySelector('.ou-toggle').addEventListener('click', e => { e.stopPropagation(); toggle(el); });
        row.addEventListener('click', () => select(el));
        row.addEventListener('keydown', e => {
            if (e.key === 'Enter') select(el);
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') toggle(el);
        });
        return el;
    }

    async function load(parentId, ul) {
        const r = await fetch(treeUrl + (parentId ? '?parent=' + parentId : ''), { headers: { Accept: 'application/json' } });
        const d = await r.json();
        ul.innerHTML = '';
        d.nodes.forEach(n => ul.appendChild(li(n)));
        loaded[parentId || 0] = true;
        return d.nodes;
    }

    async function toggle(el, forceOpen) {
        const ul = el.querySelector(':scope > ul');
        const icon = el.querySelector(':scope > .ou-row .ou-toggle i');
        if (!el._node.children) return;
        const open = forceOpen ?? ul.classList.contains('d-none');
        if (open && !loaded[el.dataset.id]) await load(el.dataset.id, ul);
        ul.classList.toggle('d-none', !open);
        icon.className = 'fas ' + (open ? 'fa-minus' : 'fa-plus');
        el.setAttribute('aria-expanded', open);
    }

    function select(el) {
        document.querySelectorAll('.ou-row.active').forEach(r => r.classList.remove('active'));
        el.querySelector(':scope > .ou-row').classList.add('active');
        detail(el._node);
    }

    function detail(n) {
        document.getElementById('detail').innerHTML = `<div class="card-body p-4">
            <div class="d-flex align-items-start gap-3 mb-3">
                <span class="eg-icon lg"><i class="fas ${esc(n.icon)}"></i></span>
                <div class="flex-grow-1">
                    <span class="badge badge-gold mb-1">${esc(n.type_label)}</span>
                    <h5 class="mb-0">${esc(n.name)}</h5>
                    ${n.code ? `<small class="text-muted en">${esc(n.code)}</small>` : ''}
                    ${n.trail ? `<div class="small text-muted mt-1">${esc(n.trail)}</div>` : ''}
                </div>
            </div>
            <div class="row g-2 text-center mb-3">
                <div class="col-4"><div class="border rounded p-2"><div class="fw-bold text-green">${fmt(n.children)}</div><small class="text-muted">جهات تابعة</small></div></div>
                <div class="col-4"><div class="border rounded p-2"><div class="fw-bold text-green">${n.users ?? '—'}</div><small class="text-muted">مستخدمون</small></div></div>
                <div class="col-4"><div class="border rounded p-2"><div class="fw-bold text-green">${n.positions ?? '—'}</div><small class="text-muted">وظائف</small></div></div>
            </div>
            <div class="d-grid gap-2">
                <a class="btn btn-kfs-primary" href="${showBase}/${n.id}"><i class="fas fa-eye ml-1"></i>عرض التفاصيل</a>
                <div class="d-flex gap-2">
                    <a class="btn btn-kfs-gold flex-fill" href="${createUrl}?parent=${n.id}"><i class="fas fa-plus ml-1"></i>جهة تابعة</a>
                    <a class="btn btn-outline-navy flex-fill" href="${showBase}/${n.id}/edit"><i class="fas fa-pen ml-1"></i>تعديل</a>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary flex-fill" href="${positionsUrl}?unit=${n.id}"><i class="fas fa-people-group ml-1"></i>الهيكل الوظيفي</a>
                    <a class="btn btn-outline-secondary flex-fill" href="${usersUrl}?unit=${n.id}&subtree=1"><i class="fas fa-users ml-1"></i>الحسابات</a>
                </div>
                <a class="btn btn-sm btn-link" href="${exportUrl}?unit=${n.id}"><i class="fas fa-file-excel ml-1"></i>تصدير هذا الفرع إلى Excel</a>
            </div></div>`;
    }

    // Search
    let timer;
    const box = document.getElementById('searchResults'), tree = document.getElementById('tree');
    document.getElementById('treeSearch').addEventListener('input', e => {
        clearTimeout(timer);
        const q = e.target.value.trim();
        if (q.length < 2) { box.classList.add('d-none'); tree.classList.remove('d-none'); return; }
        timer = setTimeout(async () => {
            const d = await (await fetch(treeUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })).json();
            tree.classList.add('d-none'); box.classList.remove('d-none');
            box.innerHTML = d.results.length ? '' : '<div class="empty-state py-3"><p class="mb-0">لا توجد نتائج</p></div>';
            d.results.forEach(n => {
                const row = document.createElement('div');
                row.className = 'ou-row';
                row.tabIndex = 0;
                row.innerHTML = `<i class="fas ${esc(n.icon)} ou-ico"></i><span class="ou-name">${esc(n.name)}<small class="d-block text-muted" style="white-space:normal">${esc(n.trail)}</small></span><span class="ou-type">${esc(n.type_label)}</span>`;
                row.addEventListener('click', () => { document.querySelectorAll('.ou-row.active').forEach(r => r.classList.remove('active')); row.classList.add('active'); detail(n); });
                box.appendChild(row);
            });
        }, 300);
    });

    // Boot: root + expand first level
    load(null, tree).then(async nodes => {
        const first = tree.querySelector(':scope > li');
        if (first) { await toggle(first, true); select(first); }
        if (selected && first && +first.dataset.id !== +selected) {
            const el = tree.querySelector(`li[data-id="${selected}"]`);
            if (el) select(el);
        }
    });
})();
</script>
@endpush
