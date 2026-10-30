@extends('layouts.app')

@section('title', 'الملف الشخصي')
@section('page-title', 'الملف الشخصي')
@section('page-subtitle', 'بيانات حسابك وموقعك في الهيكل التنظيمي والوظيفي وإعدادات الأمان')
@section('breadcrumb')
    <li class="breadcrumb-item active">الملف الشخصي</li>
@endsection

@push('styles')
<style>
    .profile-id { display:flex; align-items:center; gap:1rem; }
    .profile-id .avatar-lg { width:72px; height:72px; border-radius:50%; border:1px solid var(--egypt-gold); background:var(--egypt-green); color:var(--egypt-gold-2);
        display:flex; align-items:center; justify-content:center; font-size:1.8rem; font-weight:800; flex-shrink:0; }
    .kv { display:flex; justify-content:space-between; gap:1rem; padding:.6rem 0; border-bottom:1px solid var(--line); font-size:.86rem; }
    .kv:last-child { border-bottom:0; }
    .kv span:first-child { color:var(--text-soft); }
    .kv span:last-child { text-align:left; font-weight:600; }
</style>
@endpush

@section('content')
@php
    $user = $user ?? auth()->user();
    $roleName = $user->getRoleNames()->first() ?? '';
@endphp
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-kfs mb-4 egypt-frame" style="padding:1.5rem">
            <div class="profile-id">
                <div class="avatar-lg" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</div>
                <div class="flex-grow-1">
                    <h3 class="mb-1" style="font-size:1.35rem">{{ $user->name }}</h3>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-gold">{{ \App\Support\RoleLabels::label($roleName) }}</span>
                        @if ($user->orgUnit)<span class="badge badge-green"><i class="fas {{ $user->orgUnit->icon }} ml-1"></i>{{ $user->orgUnit->name }}</span>@endif
                        @if ($user->jobPosition)<span class="badge badge-green"><i class="fas fa-briefcase ml-1"></i>{{ $user->jobPosition->title }}</span>@endif
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="card-kfs mb-4">
            @csrf
            @method('PUT')
            <div class="card-header"><i class="fas fa-user-pen ml-2"></i>بيانات الحساب</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" class="form-control en @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email" dir="ltr">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">رقم الهاتف</label>
                        <input type="tel" class="form-control en @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel" dir="ltr">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="job_title" class="form-label">المسمى الوظيفي</label>
                        <input type="text" class="form-control @error('job_title') is-invalid @enderror" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}">
                        @error('job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-kfs-primary"><i class="fas fa-floppy-disk ml-1"></i>حفظ التعديلات</button>
            </div>
        </form>

        <form method="POST" action="{{ route('profile.password') }}" class="card-kfs mb-4">
            @csrf
            @method('PUT')
            <div class="card-header"><i class="fas fa-key ml-2"></i>تغيير كلمة المرور</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="password" class="form-label">كلمة المرور الجديدة</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required minlength="8" autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    </div>
                </div>
                <div class="form-text mt-2">8 أحرف على الأقل. يُفضّل مزج الحروف والأرقام والرموز.</div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-kfs-primary"><i class="fas fa-key ml-1"></i>تغيير كلمة المرور</button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card-kfs mb-4">
            <div class="card-header"><i class="fas fa-shield-halved ml-2"></i>المصادقة الثنائية</div>
            <div class="card-body text-center">
                @if ($user->hasTwoFactor())
                    <span class="eg-icon lg mb-2" style="color:var(--success)"><i class="fas fa-shield-halved"></i></span>
                    <h6 class="text-success">مفعّلة</h6>
                    <p class="text-muted small mb-1">منذ {{ optional($user->two_factor_confirmed_at)->locale('ar')->diffForHumans() ?? '—' }}</p>
                    <p class="text-muted small">أكواد الاسترداد المتبقية: <strong>{{ count($user->two_factor_recovery_codes ?? []) }}</strong></p>
                    <div class="d-grid gap-2 text-start">
                        <a href="{{ route('2fa.setup') }}" class="btn btn-sm btn-outline-navy"><i class="fas fa-mobile-screen ml-1"></i>تغيير جهاز المصادقة</a>
                        <form method="POST" action="{{ route('2fa.recovery.regenerate') }}">
                            @csrf
                            <label for="rc_password" class="form-label small mb-1">توليد أكواد استرداد جديدة (كلمة المرور)</label>
                            <div class="input-group input-group-sm">
                                <input type="password" id="rc_password" name="password" class="form-control" required autocomplete="current-password">
                                <button type="submit" class="btn btn-kfs-gold">توليد</button>
                            </div>
                        </form>
                        @unless (config('app.two_factor.required'))
                            <form method="POST" action="{{ route('2fa.disable') }}">
                                @csrf
                                <label for="tfa_password" class="form-label small mb-1">تعطيل (كلمة المرور)</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" id="tfa_password" name="password" class="form-control" required autocomplete="current-password">
                                    <button type="submit" class="btn btn-outline-danger">تعطيل</button>
                                </div>
                            </form>
                        @else
                            <small class="text-muted"><i class="fas fa-lock ml-1 text-gold"></i>المصادقة الثنائية إجبارية على كل الحسابات.</small>
                        @endunless
                    </div>
                @else
                    <span class="eg-icon lg mb-2" style="color:var(--warning)"><i class="fas fa-shield-halved"></i></span>
                    <h6 style="color:var(--warning)">غير مفعّلة</h6>
                    <p class="text-muted small">أضف طبقة حماية لحسابك الحكومي.</p>
                    <a href="{{ route('2fa.setup') }}" class="btn btn-kfs-gold"><i class="fas fa-shield-halved ml-1"></i>تفعيل المصادقة الثنائية</a>
                @endif
            </div>
        </div>

        <div class="card-kfs mb-4">
            <div class="card-header"><i class="fas fa-sitemap ml-2"></i>موقعي في الهيكل</div>
            <div class="card-body py-2">
                <div class="kv"><span>الجهة</span><span>{{ $user->orgUnit->name ?? '—' }}</span></div>
                @if ($user->orgUnit && $user->orgUnit->parent_id)
                    <div class="kv"><span>المسار</span><span class="small fw-normal">{{ $user->orgUnit->breadcrumb }}</span></div>
                @endif
                <div class="kv"><span>الوظيفة</span><span>{{ $user->jobPosition->title ?? '—' }}</span></div>
                @if ($user->jobPosition?->parent)
                    <div class="kv"><span>الرئيس المباشر</span><span>{{ $user->jobPosition->parent->title }}@if ($user->jobPosition->parent->holder) — {{ $user->jobPosition->parent->holder->name }}@endif</span></div>
                @endif
                <div class="kv"><span>نطاق البيانات</span><span>{{ \App\Support\DataScope::LEVELS[\App\Support\DataScope::level($user)] ?? '—' }}</span></div>
            </div>
        </div>

        <div class="card-kfs">
            <div class="card-header"><i class="fas fa-circle-info ml-2"></i>معلومات الحساب</div>
            <div class="card-body py-2">
                <div class="kv"><span>الرقم القومي</span><span class="en">{{ $user->national_id ? \Illuminate\Support\Str::mask($user->national_id, '•', 3, 8) : '—' }}</span></div>
                <div class="kv"><span>آخر دخول</span><span>{{ $user->last_login_at ? \Illuminate\Support\Carbon::parse($user->last_login_at)->diffForHumans() : '—' }}</span></div>
                <div class="kv"><span>تاريخ الانضمام</span><span>{{ $user->created_at->format('Y/m/d') }}</span></div>
                <div class="kv"><span>عدد الصلاحيات</span><span>{{ $user->getAllPermissions()->count() }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
