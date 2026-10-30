@extends('layouts.app')

@section('title', 'البحث الشامل')
@section('page-title', 'البحث الشامل')
@section('page-subtitle', $q ? 'نتائج البحث عن «' . $q . '»' : 'ابحث في الجهات والنماذج والإرسالات والأماكن')
@section('breadcrumb')
    <li class="breadcrumb-item active">البحث</li>
@endsection

@section('content')
    <form action="{{ route('search') }}" method="GET" class="mb-4" role="search">
        <div class="top-search w-100" style="height:52px;max-width:none">
            <i class="fas fa-magnifying-glass"></i>
            <input type="search" name="q" value="{{ $q }}" placeholder="ابحث في خدمات ومعلومات المحافظة…" autofocus aria-label="كلمة البحث">
            <button class="btn btn-kfs btn-sm">بحث</button>
        </div>
    </form>

    @if (mb_strlen($q) < 2)
        <div class="empty-state card-kfs">
            <span class="eg-icon lg"><i class="fas fa-magnifying-glass"></i></span>
            <h5>اكتب كلمتين على الأقل للبحث</h5>
            <p class="mb-0">يمكنك البحث باسم جهة أو مركز أو قرية، اسم نموذج، رقم مرجعي لإرسال، أو اسم منشأة.</p>
        </div>
    @elseif ($total === 0)
        <div class="empty-state card-kfs">
            <span class="eg-icon lg"><i class="far fa-folder-open"></i></span>
            <h5>لا توجد نتائج مطابقة</h5>
            <p class="mb-0">جرّب كلمة أخرى أو تحقق من الإملاء.</p>
        </div>
    @else
        <p class="text-muted">عدد النتائج: <strong class="text-green">{{ number_format($total) }}</strong></p>
        <div class="row g-4">
            @php
                $blocks = [
                    'units' => ['الجهات والتقسيم الإداري', 'fa-sitemap'],
                    'templates' => ['النماذج', 'fa-file-lines'],
                    'submissions' => ['الإرسالات', 'fa-inbox'],
                    'places' => ['الأماكن والمنشآت', 'fa-location-dot'],
                    'users' => ['الحسابات', 'fa-users'],
                    'positions' => ['الوظائف', 'fa-people-group'],
                ];
            @endphp
            @foreach ($blocks as $key => [$label, $icon])
                @continue(empty($results[$key]) || $results[$key]->isEmpty())
                <div class="col-lg-6">
                    <div class="card-kfs h-100">
                        <div class="card-header d-flex justify-content-between">
                            <span><i class="fas {{ $icon }} ml-2"></i>{{ $label }}</span>
                            <span class="badge badge-gold">{{ $results[$key]->count() }}</span>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ($results[$key] as $r)
                                @switch($key)
                                    @case('units')
                                        <a class="list-group-item list-group-item-action" href="{{ Route::has('admin.org-units.show') && auth()->user()->can('manage_org_structure') ? route('admin.org-units.show', $r) : route('map.index', ['unit' => $r->id]) }}">
                                            <i class="fas {{ $r->icon }} ml-2 text-gold"></i>{{ $r->name }}
                                            <small class="text-muted d-block">{{ $r->type_label }}</small>
                                        </a>
                                        @break
                                    @case('templates')
                                        <a class="list-group-item list-group-item-action" href="{{ auth()->user()->can('manage_form_templates') ? route('forms.templates.show', $r) : route('submissions.index', ['form_template_id' => $r->id]) }}">
                                            {{ $r->name }} <small class="text-muted">— {{ $r->form_submissions_count }} إرسال</small>
                                        </a>
                                        @break
                                    @case('submissions')
                                        <a class="list-group-item list-group-item-action" href="{{ route('submissions.show', $r) }}">
                                            <span class="en">{{ $r->reference_number }}</span> — {{ $r->formTemplate->name ?? '' }}
                                            @include('partials._status-badge', ['status' => $r->status])
                                        </a>
                                        @break
                                    @case('places')
                                        <a class="list-group-item list-group-item-action" href="{{ route('map.index', ['facility' => $r->id]) }}">
                                            <i class="fas fa-location-dot ml-2 text-gold"></i>{{ $r->name }}
                                            <small class="text-muted d-block">{{ $r->module_name }} · {{ $r->center_name }}</small>
                                        </a>
                                        @break
                                    @case('users')
                                        <a class="list-group-item list-group-item-action" href="{{ route('admin.users.edit', $r) }}">
                                            {{ $r->name }} <small class="text-muted">— {{ $r->orgUnit->name ?? '' }}</small>
                                        </a>
                                        @break
                                    @case('positions')
                                        <a class="list-group-item list-group-item-action" href="{{ route('admin.positions.index', ['unit' => $r->org_unit_id]) }}">
                                            {{ $r->title }} <small class="text-muted">— {{ $r->orgUnit->name ?? '' }} · {{ $r->holder->name ?? 'شاغرة' }}</small>
                                        </a>
                                        @break
                                @endswitch
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
