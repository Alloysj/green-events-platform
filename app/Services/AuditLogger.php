<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    public function log(string $event, array $metadata = [], ?User $actor = null): void
    {
        if (! tenant()) {
            return;
        }

        $request = request();

        AuditLog::query()->create([
            'user_id' => $actor?->id,
            'event' => $event,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
