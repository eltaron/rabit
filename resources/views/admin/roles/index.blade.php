@extends('layouts.app')
@use('App\Support\RoleLabels')

@section('title', 'الأدوار والصلاحيات')
@section('page-title', 'الأدوار والصلاحيات')
@section('page-subtitle', 'تحكم كامل في ما يستطيع كل دور رؤيته وتنفيذه داخل المنصة')
@section('breadcrumb')
    <li class="breadcrumb-item active">الأدوار والصلاحيات</li>
@endsection
@section('page-actions')
    <a href="{{ route('admin.roles.create') }}" class="btn btn-kfs-primary"><i class="fas fa-plus ml-1"></i>دور جديد</a>
@endsection

@push('styles')
<style>
    .role-card .rc-head { display: flex; gap: .8rem; align-items: center; }
    .role-card .perm-bar { height: 4px; background: var(--sand); border-radius: 2px; overflow: hidden; }
    .role-card .perm-bar span { display: block; height: 100%; background: var(--egypt-gold); }
    .role-card .perm-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
    .role-card .perm-tags span { font-size: .68rem; padding: .1rem .45rem; border: 1px solid var(--line); border-radius: 4px; color: var(--text-soft); background: var(--ivory); }
    .matrix-wrap { overflow: auto; max-height: 72vh; }
    .matrix { border-collapse: separate; border-spacing: 0; min-width: 100%; }
    .matrix th, .matrix td { padding: .45rem .6rem; border-bottom: 1px solid var(--line); font-size: .8rem; background: var(--white-soft); }
    .matrix thead th { position: sticky; top: 0; z-index: 2; background: var(--egypt-green); color: var(--white-soft); text-align: center; white-space: nowrap; font-family: var(--ff-ar); }
    .matrix thead th:first-child { right: 0; z-index: 3; text-align: right; }
    .matrix tbody th { position: sticky; right: 0; z-index: 1; font-weight: 500; text-align: right; min-width: 240px; }
    .matrix td { text-align: center; }
    .matrix tr.grp th { background: var(--papyrus); color: var(--egypt-green); font-weight: 800; }
    .matrix tr.grp td { background: var(--papyrus); }
    .matrix tbody tr:hover th, .matrix tbody tr:hover td { background: var(--ivory); }
    .matrix .code { font-family: var(--ff-en); font-size: .66rem; color: var(--muted); display: block; }
</style>
@endpush

@section('content')
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link {{ request('tab') !== 'matrix' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabRoles" type="button" role="tab"><i class="fas fa-user-shield ml-1"></i>الأدوار</button></li>
        <li class="nav-item"><button class="nav-link {{ request('tab') === 'matrix' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabMatrix" type="button" role="tab"><i class="fas fa-table-cells ml-1"></i>مصفوفة الصلاحيات</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ request('tab') !== 'matrix' ? 'show active' : '' }}" id="tabRoles" role="tabpanel">
            <div class="row g-4">
                @foreach ($roles as $role)
                    @php $pct = $totalPermissions ? round($role->permissions_count / $totalPermissions * 100) : 0; @endphp
                    <div class="col-xl-4 col-md-6">
                        <div class="service-card role-card">
                            <div class="rc-head">
                                <span class="eg-icon"><i class="fas {{ $role->name === 'super_admin' ? 'fa-crown' : (in_array($role->name, ['governor', 'leader']) ? 'fa-chess-king' : 'fa-user-shield') }}"></i></span>
                                <div class="flex-grow-1">
                                    <h3 class="mb-0">{{ RoleLabels::label($role->name) }}</h3>
                                    <small class="text-muted en">{{ $role->name }}</small>
                                </div>
                                <span class="badge badge-green">{{ $role->users_count }} مستخدم</span>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between small mb-1"><span class="text-muted">الصلاحيات</span><b class="text-green">{{ $role->permissions_count }} / {{ $totalPermissions }}</b></div>
                                <div class="perm-bar"><span style="width: {{ $pct }}%"></span></div>
                            </div>
                            <div class="perm-tags">
                                @foreach ($role->permissions->take(6) as $p)
                                    <span>{{ RoleLabels::permissionLabel($p->name) }}</span>
                                @endforeach
                                @if ($role->permissions->count() > 6)<span>+{{ $role->permissions->count() - 6 }}</span>@endif
                            </div>
                            <div class="d-flex gap-2 mt-auto pt-2">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-kfs-primary flex-fill"><i class="fas fa-sliders ml-1"></i>تعديل الصلاحيات</a>
                                @can('manage_users')<a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="btn btn-sm btn-outline-secondary" title="المستخدمون"><i class="fas fa-users"></i></a>@endcan
                                @if ($role->name !== 'super_admin' && $role->users_count === 0)
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('{{ route('admin.roles.destroy', $role) }}', @js(RoleLabels::label($role->name)))" title="حذف"><i class="fas fa-trash"></i></button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="tab-pane fade {{ request('tab') === 'matrix' ? 'show active' : '' }}" id="tabMatrix" role="tabpanel">
            <form method="POST" action="{{ route('admin.roles.matrix') }}">
                @csrf @method('PUT')
                <div class="card-kfs">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span><i class="fas fa-table-cells ml-2"></i>الأدوار × الصلاحيات</span>
                        <div class="d-flex gap-2 align-items-center">
                            <small class="text-muted">صلاحيات مدير النظام ثابتة (كاملة)</small>
                            <button class="btn btn-kfs-primary btn-sm"><i class="fas fa-floppy-disk ml-1"></i>حفظ المصفوفة</button>
                        </div>
                    </div>
                    <div class="matrix-wrap">
                        <table class="matrix">
                            <thead>
                                <tr>
                                    <th>الصلاحية</th>
                                    @foreach ($roles as $role)<th>{{ RoleLabels::label($role->name) }}</th>@endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $gkey => [$glabel, $gicon, $perms])
                                    <tr class="grp">
                                        <th><i class="fas {{ $gicon }} ml-2 text-gold"></i>{{ $glabel }}</th>
                                        @foreach ($roles as $role)
                                            <td>@if ($role->name !== 'super_admin')<input type="checkbox" class="form-check-input grp-toggle" data-role="{{ $role->id }}" data-group="{{ $gkey }}" aria-label="تحديد المجموعة">@endif</td>
                                        @endforeach
                                    </tr>
                                    @foreach ($perms as $perm => $plabel)
                                        <tr>
                                            <th>{{ $plabel }}<span class="code">{{ $perm }}</span></th>
                                            @foreach ($roles as $role)
                                                <td>
                                                    <input type="checkbox" class="form-check-input" name="matrix[{{ $role->id }}][]" value="{{ $perm }}"
                                                           data-role="{{ $role->id }}" data-group="{{ $gkey }}"
                                                           @checked($role->name === 'super_admin' || $role->permissions->contains('name', $perm))
                                                           @disabled($role->name === 'super_admin')
                                                           aria-label="{{ $plabel }} — {{ RoleLabels::label($role->name) }}">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.grp-toggle').forEach(t => {
        const boxes = () => document.querySelectorAll(`input[name^="matrix"][data-role="${t.dataset.role}"][data-group="${t.dataset.group}"]`);
        const sync = () => { const b = [...boxes()]; t.checked = b.length && b.every(x => x.checked); t.indeterminate = !t.checked && b.some(x => x.checked); };
        t.addEventListener('change', () => boxes().forEach(b => b.checked = t.checked));
        boxes().forEach(b => b.addEventListener('change', sync));
        sync();
    });
</script>
@endpush
