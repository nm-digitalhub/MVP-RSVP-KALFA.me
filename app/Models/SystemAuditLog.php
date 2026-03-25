<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $actor_id
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string $action
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $actor
 * @property-read Model|\Eloquent|null $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereTargetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereUserAgent($value)
 * @mixin \Eloquent
 * @mixin IdeHelperSystemAuditLog
 */
class SystemAuditLog extends Model
{
    protected $fillable = [
        'actor_id',
        'target_type',
        'target_id',
        'action',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
