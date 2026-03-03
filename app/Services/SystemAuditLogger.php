<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemAuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class SystemAuditLogger
{
    public static function log(
        ?Authenticatable $actor,
        string $action,
        Model|int|null $target = null,
        array $metadata = [],
    ): SystemAuditLog {
        $targetType = null;
        $targetId = null;
        if ($target instanceof Model) {
            $targetType = $target->getMorphClass();
            $targetId = $target->getKey();
        } elseif (is_int($target)) {
            $targetId = $target;
        }

        return SystemAuditLog::create([
            'actor_id' => $actor?->getAuthIdentifier(),
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'metadata' => $metadata ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
