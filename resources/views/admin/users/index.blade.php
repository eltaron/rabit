@extends('layouts.app')
@use('App\Support\RoleLabels')
@use('App\Support\DataScope')

@section('title', 'الحسابات')
@section('page-title', 'إدارة الحسابات')
@section('page-subtitle', 'الحسابات وجهاتها ووظائفها وأدوارها ونطاق اطلاعها على البيانات')
@section('breadcrumb')
    <li class="breadcrumb-item active">الحسابات</li>
@endsection
@section('page-actions')
    <a href="{{ route('admin.users.create', array_filter(['unit' => request('unit')])) }}" class="btn btn-kfs-primary"><i class="fas fa-user-plus ml-1"></i>حساب جديد</a>
    <div class="dropdown">
        <button class="btn btn-kfs-gold dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-file-excel ml-1"></i>Excel</button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('admin.users.template') }}"><i class="fas fa-download ml-2 text-gold"></i>تحميل قالب فارغ</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.users.export') }}"><i class="fas fa-file-export ml-2 text-gold"></i>تصدير كل الحسابات</a></li>
            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-upload ml-2 text-gold"></i>رفع ملف حسابات</button></li>
        </ul>
    </div>
@endsection

@section('content')
    @if (session('new_password'))
        @php $np = session('new_password'); @endphp
        <div class="alert alert-warning d-flex align-items-center gap-3 flex-wrap" role="alert">
            <i class="fas fa-key fa-lg"></i>
            <div class="flex-grow-1">
                <div class="fw-bold">كلمة المرور الجديدة للحساب «{{ $np['name'] }}» — تظهر مرة واحدة فقط</div>
                <code class="en fs-6 user-select-all" id="newPwd">{{ $np['password'] }}</code>
            </div>
            <button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard?.writeText(document.getElementById('newPwd').textContent);this.textContent='تم النسخ'">نسخ</button>
        </div>
    @endif
    @if (session('new_passwords'))
        <div class="card-kfs mb-4">
            <div class="card-header"><i class="fas fa-key ml-2"></i>كلمات مرور الحسابات الجديدة (تظهر مرة واحدة — احفظها وسلّمها لأصحابها)</div>
            <div class="table-responsive"><table class="table table-kfs mb-0"><thead><tr><th>الاسم</th><th>البريد</th><th>كلمة المرور</th></tr></thead><tbody>
                @foreach (session('new_passwords') as $p)
                    <tr><td>{{ $p['name'] }}</td><td class="en">{{ $p['email'] }}</td><td><code class="en user-select-all">{{ $p['password'] }}</code></td></tr>
                @endforeach
            </tbody></table></div>
        </div>
    @endif

    <div class="gov-stats mb-4">
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-users"></i></div><div class="gs-num">{{ number_format($stats['total']) }}</div><div class="gs-label">إجمالي الحسابات</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-user-check"></i></div><div class="gs-num" style="color:var(--success)">{{ number_format($stats['active']) }}</div><div class="gs-label">حسابات نشطة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-user-slash"></i></div><div class="gs-num" style="color:var(--danger)">{{ number_format($stats['inactive']) }}</div><div class="gs-label">حسابات معطلة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-shield-halved"></i></div><div class="gs-num">{{ number_format($stats['twofa']) }}</div><div class="gs-label">مصادقة ثنائية مفعلة</div></div>
        <div class="gs-item"><div class="gs-icon"><i class="fas fa-circle-question"></i></div><div class="gs-num" style="color:var(--warning)">{{ number_format($stats['no_unit']) }}</div><div class="gs-label">بدون جهة</div></div>
    </div>

    <form method="GET" class="card-kfs mb-4">
        <div class="card-body row g-2 align-items-end">
            <div class="col-lg-3 col-md-6"><label class="form-label" for="f_search">بحث</label><input class="form-control" id="f_search" name="search" value="{{ request('search') }}" placeholder="الاسم، البريد، الرقم القومي…"></div>
            <div class="col-lg-2 col-md-6"><label class="form-label" for="f_role">الدور</label>
                <select class="form-select" id="f_role" name="role"><option value="">الكل</option>@foreach ($roles as $r)<option value="{{ $r }}" @selected(request('role') === $r)>{{ RoleLabels::label($r) }}</option>@endforeach</select></div>
            <div class="col-lg-3 col-md-6"><label class="form-label" for="f_unit">الجهة</label>
                <select class="form-select" id="f_unit" name="unit"><option value="">كل الجهات</option>@foreach ($units as $id => $label)<option value="{{ $id }}" @selected((int) request('unit') === $id)>{{ $label }}</option>@endforeach</select>
                <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="subtree" value="1" id="f_sub" @checked(request('subtree'))><label class="form-check-label small" for="f_sub">تشمل الجهات التابعة</label></div></div>
            <div class="col-lg-2 col-md-3"><label class="form-label" for="f_status">الحالة</label>
                <select class="form-select" id="f_status" name="status"><option value="">الكل</option><option value="active" @selected(request('status') === 'active')>نشط</option><option value="inactive" @selected(request('status') === 'inactive')>معطل</option></select></div>
            <div class="col-lg-2 col-md-3 d-flex gap-2"><button class="btn btn-kfs-primary flex-fill"><i class="fas fa-filter ml-1"></i>تصفية</button><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary" title="مسح"><i class="fas fa-xmark"></i></a></div>
        </div>
    </form>

    <div class="card-kfs">
        <div class="card-header d-flex justify-content-between"><span><i class="fas fa-users-gear ml-2"></i>الحسابات ({{ number_format($users->total()) }})</span></div>
        @if ($users->isEmpty())
            <div class="empty-state"><span class="eg-icon lg"><i class="fas fa-user"></i></span><h5>لا توجد حسابات مطابقة</h5></div>
        @else
            <div class="table-responsive">
                <table class="table table-kfs mb-0">
                    <thead><tr><th>الحساب</th><th>الجهة / الوظيفة</th><th>الدور</th><th>نطاق البيانات</th><th>الحالة</th><th>آخر دخول</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($users as $u)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-chip p-0 border-0 bg-transparent"><div class="avatar">{{ mb_substr($u->name, 0, 1) }}</div></div>
                                        <div><div class="fw-bold">{{ $u->name }}</div><small class="text-muted en">{{ $u->email }}</small>@if ($u->national_id)<small class="text-muted d-block en">{{ $u->national_id }}</small>@endif</div>
                                    </div>
                                </td>
                                <td><div>{{ $u->orgUnit->name ?? '—' }}</div><small class="text-muted">{{ $u->jobPosition->title ?? $u->job_title }}</small></td>
                                <td>@foreach ($u->roles as $r)<span class="badge {{ $r->name === 'super_admin' ? 'badge-gold' : 'badge-green' }} mb-1">{{ RoleLabels::label($r->name) }}</span> @endforeach</td>
                                <td><small>{{ DataScope::LEVELS[$u->data_scope] ?? '—' }}</small></td>
                                <td>{!! $u->is_active ? '<span class="status-pill status-success">نشط</span>' : '<span class="status-pill status-error">معطل</span>' !!}@if ($u->two_factor_enabled) <i class="fas fa-shield-halved text-success" title="مصادقة ثنائية"></i>@endif</td>
                                <td><small class="text-muted">{{ $u->last_login_at ? \Illuminate\Support\Carbon::parse($u->last_login_at)->diffForHumans() : 'لم يسجل' }}</small></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="تعديل" aria-label="تعديل"><i class="fas fa-pen"></i></a>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-secondary btn-icon" data-bs-toggle="dropdown" aria-label="إجراءات"><i class="fas fa-ellipsis-vertical"></i></button>
                                        <ul class="dropdown-menu">
                                            <li><form method="POST" action="{{ route('admin.users.toggle', $u) }}">@csrf<button class="dropdown-item"><i class="fas {{ $u->is_active ? 'fa-user-slash' : 'fa-user-check' }} ml-2 text-gold"></i>{{ $u->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}</button></form></li>
                                            <li><form method="POST" action="{{ route('admin.users.reset-password', $u) }}" onsubmit="return confirm('توليد كلمة مرور جديدة لهذا الحساب؟')">@csrf<button class="dropdown-item"><i class="fas fa-key ml-2 text-gold"></i>إعادة تعيين كلمة المرور</button></form></li>
                                            <li><form method="POST" action="{{ route('admin.users.reset-2fa', $u) }}" onsubmit="return confirm('إعادة ضبط المصادقة الثنائية لهذا الحساب؟ سيُطلب منه إعدادها من جديد عند الدخول.')">@csrf<button class="dropdown-item" @disabled(! $u->hasTwoFactor())><i class="fas fa-shield-halved ml-2 text-gold"></i>إعادة ضبط المصادقة الثنائية</button></form></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button class="dropdown-item text-danger" onclick="confirmDelete('{{ route('admin.users.destroy', $u) }}', @js($u->name))"><i class="fas fa-trash ml-2"></i>حذف</button></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())<div class="p-3">{{ $users->links() }}</div>@endif
        @endif
    </div>

    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">رفع ملف الحسابات</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">يُحدَّث الحساب الموجود (بالبريد أو الرقم القومي) ويُنشأ الجديد. الحسابات الجديدة بلا كلمة مرور تحصل على كلمة مرور مولدة تظهر بعد الرفع مرة واحدة.</p>
                    <label class="drop-zone d-block">
                        <i class="fas fa-file-arrow-up fa-2x text-gold mb-2"></i>
                        <div class="fw-bold text-green">اسحب الملف هنا أو اضغط للاختيار</div>
                        <div class="dz-name small mt-2 text-green fw-bold"></div>
                        <input type="file" name="file" accept=".xlsx,.xls" class="d-none" required>
                    </label>
                </div>
                <div class="modal-footer"><a href="{{ route('admin.users.template') }}" class="btn btn-outline-navy">القالب</a><button class="btn btn-kfs-primary">رفع ومعالجة</button></div>
            </form>
        </div>
    </div>
@endsection
