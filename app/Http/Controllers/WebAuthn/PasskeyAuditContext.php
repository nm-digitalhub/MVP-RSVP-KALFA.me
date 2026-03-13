<?php

declare(strict_types=1);

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Http\Request;

/**
 * Shared audit context for all WebAuthn log events.
 */
trait PasskeyAuditContext
{
    /** Current event schema version — bump when log structure changes. */
    private const EVENT_VERSION = 'v1';

    /**
     * Returns base audit fields common to every passkey log event.
     *
     * @return array<string, string|null>
     */
    private function auditContext(Request $request): array
    {
        return [
            'event_version' => self::EVENT_VERSION,
            'request_id' => $request->attributes->get('request_id', $request->header('X-Request-Id')),
            'auth_method' => 'passkey',
            'ip' => $request->ip(),
            'ua_hash' => substr(hash('sha256', $request->userAgent() ?? ''), 0, 16),
        ];
    }
}
