@extends('layouts.app')
@use('App\Support\RoleLabels')

@php $editing = $user->exists; @endphp
@section('title', $editing ? 'تعديل حساب' : 'حساب جديد')
@section('page-title', $editing ? 'تعديل حساب: ' . $user->name : 'إنشاء حساب جديد')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">الحسابات</a></li>
    <li class="breadcrumb-item active">{{ $editing ? 'تعديل' : 'إنشاء' }}</li>
@endsection

@section('content')
<form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" autocomplete="off">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-kfs mb-4">
                <div class="card-header"><i class="fas fa-id-card ml-2"></i>بيانات الحساب</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label" for="name">الاسم الرباعي <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label" for="email">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" class="form-control en @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="national_id">الرقم القومي</label>
                        <input class="form-control en @error('national_id') is-invalid @enderror" id="national_id" name="national_id" value="{{ old('national_id', $user->national_id) }}" inputmode="numeric" maxlength="14" dir="ltr">
                        @error('national_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="phone">الهاتف</label><input class="form-control en" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr"></div>
                    <div class="col-md-4"><label class="form-label" for="job_title">المسمى الوظيفي</label><input class="form-control" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}"></div>
                    <div class="col-md-6"><label class="form-label" for="password">كلمة المرور @unless ($editing)<span class="text-danger">*</span>@endunless</label>
                        <div class="input-group">
                            <input type="password" class="form-control en @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" {{ $editing ? '' : 'required' }} minlength="8">
                            <button type="button" class="btn btn-outline-secondary" onclick="genPwd()" title="توليد"><i class="fas fa-wand-magic-sparkles"></i></button>
                        </div>
                        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                        @if ($editing)<div class="form-text">اتركها فارغة للإبقاء على كلمة المرور الحالية.</div>@endif
                    </div>
                    <div class="col-md-6"><label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
                        <input type="password" class="form-control en" id="password_confirmation" name="password_confirmation" autocomplete="new-password"></div>
                </div>
            </div>

            <div class="card-kfs mb-4">
                <div class="card-header"><i class="fas fa-sitemap ml-2"></i>الجهة والوظيفة</div>
                <div class="card-body row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="org_unit_id">الجهة <span class="text-danger">*</span></label>
                        <input type="search" class="form-control form-control-sm mb-1" id="unitFilter" placeholder="تصفية قائمة الجهات…" aria-label="تصفية الجهات">
                        <select class="form-select @error('org_unit_id') is-invalid @enderror" id="org_unit_id" name="org_unit_id" required size="8">
                            @foreach ($units as $id => $label)
                                <option value="{{ $id }}" @selected((int) old('org_unit_id', $user->org_unit_id) === $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('org_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="job_position_id">الوظيفة في الهيكل الوظيفي</label>
                        <select class="form-select" id="job_position_id" name="job_position_id">
                            <option value="">— بدون —</option>
                        </select>
                        <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="allPositions"><label class="form-check-label small" for="allPositions">عرض وظائف كل الجهات</label></div>
                        <div class="form-text">تُعرض وظائف الجهة المختارة. الوظيفة المشغولة بآخر ستُنقل لهذا الحساب.</div>
                    </div>
                </div>
            </div>

            <div class="card-kfs">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-key ml-2"></i>صلاحيات إضافية مباشرة</span>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#directPerms">عرض / إخفاء</button>
                </div>
                <div class="collapse {{ ! empty(old('permissions', $directPermissions)) ? 'show' : '' }}" id="directPerms">
                    <div class="card-body">
                        <p class="small text-muted">تُضاف هذه الصلاحيات لهذا الحساب فقط بالإضافة إلى صلاحيات أدواره. الأفضل إدارة الصلاحيات عبر الأدوار.</p>
                        <div class="row g-3">
                            @foreach ($groups as $gkey => [$glabel, $gicon, $perms])
                                <div class="col-md-6">
                                    <div class="fw-bold text-green small mb-1"><i class="fas {{ $gicon }} ml-1 text-gold"></i>{{ $glabel }}</div>
                                    @foreach ($perms as $perm => $plabel)
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $perm }}" id="dp_{{ $perm }}" @checked(in_array($perm, old('permissions', $directPermissions), true))><label class="form-check-label small" for="dp_{{ $perm }}">{{ $plabel }}</label></div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-kfs mb-4">
                <div class="card-header"><i class="fas fa-user-shield ml-2"></i>الأدوار <span class="text-danger">*</span></div>
                <div class="card-body">
                    @error('roles')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    @foreach ($roles as $r)
                        <div class="form-check py-1 border-bottom">
                            <input class="form-check-input role-cb" type="checkbox" name="roles[]" value="{{ $r }}" id="r_{{ $r }}" @checked(in_array($r, old('roles', $assignedRoles), true))>
                            <label class="form-check-label" for="r_{{ $r }}">{{ RoleLabels::label($r) }} <small class="text-muted en">{{ $r }}</small></label>
                        </div>
                    @endforeach
                    <a href="{{ route('admin.roles.index') }}" class="small d-inline-block mt-2"><i class="fas fa-sliders ml-1"></i>إدارة الأدوار وصلاحياتها</a>
                </div>
            </div>

            <div class="card-kfs mb-4">
                <div class="card-header"><i class="fas fa-eye ml-2"></i>نطاق الاطلاع على البيانات</div>
                <div class="card-body">
                    @foreach ($scopes as $k => $label)
                        <div class="form-check py-1">
                            <input class="form-check-input" type="radio" name="data_scope" value="{{ $k }}" id="s_{{ $k }}" @checked(old('data_scope', $user->data_scope ?? 'unit') === $k)>
                            <label class="form-check-label" for="s_{{ $k }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    <div class="form-text">صلاحية «الاطلاع على بيانات كل المحافظة» في الدور تتجاوز هذا الإعداد.</div>
                </div>
            </div>

            <div class="card-kfs mb-4">
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                        <label class="form-check-label fw-bold" for="is_active">الحساب نشط ويمكنه تسجيل الدخول</label>
                    </div>
                    @if ($editing)
                        <hr>
                        <dl class="row small mb-0">
                            <dt class="col-6 text-muted">آخر دخول</dt><dd class="col-6">{{ $user->last_login_at ?? '—' }}</dd>
                            <dt class="col-6 text-muted">المصادقة الثنائية</dt><dd class="col-6">{{ $user->two_factor_enabled ? 'مفعلة' : 'غير مفعلة' }}</dd>
                            <dt class="col-6 text-muted">تاريخ الإنشاء</dt><dd class="col-6">{{ $user->created_at?->format('Y-m-d') }}</dd>
                        </dl>
                    @endif
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-kfs-primary btn-lg"><i class="fas fa-check ml-1"></i>{{ $editing ? 'حفظ التعديلات' : 'إنشاء الحساب' }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    @php
        $posData = $positions->map(fn ($p) => ['id' => $p->id, 'title' => $p->title, 'unit' => $p->org_unit_id, 'unit_name' => $p->orgUnit->name ?? '', 'taken' => $p->user_id && $p->user_id !== $user->id])->values();
        $currentPos = (int) old('job_position_id', $user->job_position_id);
    @endphp
    const positions = {!! json_encode($posData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    const current = {{ $currentPos }};
    const defaultScopes = @json($defaultScopes);
    const unitSel = document.getElementById('org_unit_id'), posSel = document.getElementById('job_position_id'), all = document.getElementById('allPositions');

    function renderPositions() {
        const unit = +unitSel.value;
        const list = all.checked ? positions : positions.filter(p => p.unit === unit || p.id === current);
        posSel.innerHTML = '<option value="">— بدون —</option>';
        list.forEach(p => {
            const o = new Option((all.checked ? p.unit_name + ' — ' : '') + p.title + (p.taken ? ' (مشغولة)' : ''), p.id);
            if (p.id === current) o.selected = true;
            posSel.appendChild(o);
        });
    }
    unitSel.addEventListener('change', renderPositions);
    all.addEventListener('change', renderPositions);
    renderPositions();
    unitSel.querySelector('option:checked')?.scrollIntoView({ block: 'nearest' });

    document.getElementById('unitFilter').addEventListener('input', e => {
        const q = e.target.value.trim();
        [...unitSel.options].forEach(o => o.hidden = q && !o.text.includes(q));
    });

    // اقتراح نطاق البيانات حسب الدور عند الإنشاء
    @unless ($editing)
    document.querySelectorAll('.role-cb').forEach(cb => cb.addEventListener('change', () => {
        if (cb.checked && defaultScopes[cb.value]) document.getElementById('s_' + defaultScopes[cb.value]).checked = true;
    }));
    @endunless

    window.genPwd = function () {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789@#';
        let p = 'Kfs@'; const a = new Uint32Array(8); crypto.getRandomValues(a); a.forEach(n => p += chars[n % chars.length]);
        ['password', 'password_confirmation'].forEach(id => { const el = document.getElementById(id); el.value = p; el.type = 'text'; });
    };
})();
</script>
@endpush
