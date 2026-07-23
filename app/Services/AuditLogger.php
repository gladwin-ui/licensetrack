<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public static function log(string $action, $subject = null, ?string $description = null): void
    {
        $userId = Auth::user()?->id;
        
        $subjectType = null;
        $subjectId = null;

        if ($subject) {
            if (is_object($subject)) {
                $subjectType = get_class($subject);
                $subjectId = $subject->id ?? null;
            } elseif (is_array($subject)) {
                $subjectType = $subject['type'] ?? null;
                $subjectId = $subject['id'] ?? null;
            }
        }

        AuditLog::create([
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'description'  => $description,
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
        ]);
    }
}
