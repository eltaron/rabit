<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait WritesAudit
{
    protected function audit(string $event, $model, string $description, ?array $old = null, ?array $new = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'event' => $event,
            'old_values' => $old,
            'new_values' => $new,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 250),
        ]);
    }
}
