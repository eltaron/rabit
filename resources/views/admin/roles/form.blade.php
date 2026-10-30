@extends('layouts.app')

@php $editing = $role->exists; $locked = $role->name === 'super_admin'; @endphp
@section('title', $editing ? 'تعديل دور' : 'دور جديد')
@section('page-title', $editing ? 'صلاحيات: ' . $label : 'إنشاء دور جديد')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">الأدوار والصلاحيات</a></li>
    <li class="breadcrumb-item active">{{ $editing ? 'تعديل' : 'إنشاء' }}</li>
@endsection

@push('styles')
<style>
    .perm-group { border: 1px solid var(--line); border-radius: 8px; background: var(--white-soft); height: 100%; }
    .perm-group .pg-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem 1rem; border-bottom: 1px solid var(--line); background: var(--papyrus); border-radius: 8px 8px 0 0; }
    .perm-group .pg-head label { font-family: var(--ff-ar); font-weight: 800; color: var(--egypt-green); margin: 0; cursor: pointer; }
    .perm-group .pg-body { padding: .6rem 1rem; }
    .perm-group .form-check { padding: .3rem 0 .3rem 0; padding-right: 1.6em; margin: 0; border-bottom: 1px dashed var(--line); }
    .perm-group .form-check:last-child { border-bottom: 0; }
    .perm-group .form-check-label { font-size: .85rem; }
    .perm-group .code { font-family: var(--ff-en); font-size: .66rem; color: var(--muted); }
</style>
@endpush

@section('content')
<form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="card-kfs mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="label">اسم الدور (يظهر للمستخدمين)</label>
                <input class="form-control" id="label" name="label" value="{{ old('label', $label) }}" placeholder="مثال: مسؤول بيانات الصحة" maxlength="100">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="name">المعرّف التقني @unless ($editing)<span class="text-danger">*</span>@endunless</label>
                <input class="form-control en @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" dir="ltr" {{ $editing ? 'disabled' : 'required' }} placeholder="health_data_officer" pattern="[a-z][a-z0-9_]*">
                <div class="form-text">حروف إنجليزية صغيرة وشرطة سفلية — لا يمكن تغييره لاحقاً.</div>
            </div>
            <div class="col-md-3 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('.perm-cb:not(:disabled)').forEach(c=>c.checked=true);syncAll()">تحديد الكل</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('.perm-cb:not(:disabled)').forEach(c=>c.checked=false);syncAll()">إلغاء الكل</button>
            </div>
            @if ($locked)
                <div class="col-12"><div class="alert alert-info mb-0">دور مدير النظام يملك جميع الصلاحيات دائماً ولا يمكن تقليصها.</div></div>
            @endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach ($groups as $gkey => [$glabel, $gicon, $perms])
            <div class="col-xl-4 col-md-6">
                <div class="perm-group">
                    <div class="pg-head">
                        <label for="g_{{ $gkey }}"><i class="fas {{ $gicon }} ml-2 text-gold"></i>{{ $glabel }}</label>
                        <input type="checkbox" class="form-check-input grp" id="g_{{ $gkey }}" data-group="{{ $gkey }}" @disabled($locked) aria-label="تحديد كل صلاحيات {{ $glabel }}">
                    </div>
                    <div class="pg-body">
                        @foreach ($perms as $perm => $plabel)
                            <div class="form-check">
                                <input class="form-check-input perm-cb" type="checkbox" name="permissions[]" value="{{ $perm }}" id="p_{{ $perm }}" data-group="{{ $gkey }}"
                                       @checked($locked || in_array($perm, old('permissions', $assigned), true)) @disabled($locked)>
                                <label class="form-check-label" for="p_{{ $perm }}">{{ $plabel }} <span class="code d-block">{{ $perm }}</span></label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        <button class="btn btn-kfs-primary"><i class="fas fa-check ml-1"></i>{{ $editing ? 'حفظ الصلاحيات' : 'إنشاء الدور' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function syncAll() {
        document.querySelectorAll('.grp').forEach(g => {
            const b = [...document.querySelectorAll(`.perm-cb[data-group="${g.dataset.group}"]`)];
            g.checked = b.every(x => x.checked); g.indeterminate = !g.checked && b.some(x => x.checked);
        });
    }
    document.querySelectorAll('.grp').forEach(g => g.addEventListener('change', () => {
        document.querySelectorAll(`.perm-cb[data-group="${g.dataset.group}"]`).forEach(c => c.checked = g.checked);
    }));
    document.querySelectorAll('.perm-cb').forEach(c => c.addEventListener('change', syncAll));
    syncAll();
</script>
@endpush
