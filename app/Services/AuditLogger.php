<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an event.
     *
     * @param string $event E.g., 'Settings Changed', 'Login'
     * @param string $action E.g., 'System Settings Changed'
     * @param string $description Human-readable details
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param int|null $userId Provide explicitly, otherwise uses auth()->id()
     */
    public static function log($event, $action = null, $description = null, $oldValues = null, $newValues = null, $userId = null)
    {
        AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
