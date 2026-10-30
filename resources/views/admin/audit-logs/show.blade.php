@extends('layouts.app')
@use('App\Http\Controllers\Audit\AuditLogController', 'Audit')

@section('title', 'تفاصيل عملية')
@section('page-title', Audit::eventLabel($log->event) . ' — ' . Audit::typeLabel($log->auditable_type))
@section('page-subtitle', $log->created_at->format('Y-m-d H:i:s'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.audit-logs.index') }}">سجل التدقيق</a></li>
    <li class="breadcrumb-item active">#{{ $log->id }}</li>
@endsection

@php
    $fmt = function ($v) {
        if (is_null($v) || $v === '') return '—';
        if (is_bool($v)) return $v ? 'نعم' : 'لا';
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return (string) $v;
    };
@endphp

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-kfs egypt-frame">
                <div class="card-body p-4">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">المستخدم</dt><dd class="col-7">{{ $log->user->name ?? 'النظام' }}<small class="d-block en text-muted">{{ $log->user->email ?? '' }}</small></dd>
                        <dt class="col-5 text-muted">العملية</dt><dd class="col-7">{{ Audit::eventLabel($log->event) }}</dd>
                        <dt class="col-5 text-muted">العنصر</dt><dd class="col-7">{{ Audit::typeLabel($log->auditable_type) }} @if ($log->auditable_id)<span class="en">#{{ $log->auditable_id }}</span>@endif</dd>
                        <dt class="col-5 text-muted">الوقت</dt><dd class="col-7 en">{{ $log->created_at->format('Y-m-d H:i:s') }}</dd>
                        <dt class="col-5 text-muted">عنوان IP</dt><dd class="col-7 en">{{ $log->ip_address ?: '—' }}</dd>
                        <dt class="col-5 text-muted">المتصفح</dt><dd class="col-7"><small class="en text-muted" style="word-break:break-all">{{ $log->user_agent ?: '—' }}</small></dd>
                    </dl>
                    <hr>
                    <p class="mb-0">{{ $log->description }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card-kfs">
                <div class="card-header"><i class="fas fa-code-compare ml-2"></i>القيم قبل وبعد</div>
                @if ($keys->isEmpty())
                    <div class="empty-state"><p class="mb-0">لا توجد قيم مسجلة لهذه العملية.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-kfs mb-0">
                            <thead><tr><th>الحقل</th><th>قبل</th><th>بعد</th></tr></thead>
                            <tbody>
                                @foreach ($keys as $k)
                                    @php $o = $old[$k] ?? null; $n = $new[$k] ?? null; $changed = $fmt($o) !== $fmt($n); @endphp
                                    <tr @if ($changed) style="background:var(--egypt-gold-soft)" @endif>
                                        <td class="en small fw-bold">{{ $k }}</td>
                                        <td><pre class="mb-0 small" style="white-space:pre-wrap;font-family:inherit;color:var(--danger)">{{ array_key_exists($k, $old) ? $fmt($o) : '' }}</pre></td>
                                        <td><pre class="mb-0 small" style="white-space:pre-wrap;font-family:inherit;color:var(--success)">{{ array_key_exists($k, $new) ? $fmt($n) : '' }}</pre></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
