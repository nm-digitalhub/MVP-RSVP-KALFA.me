<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $source
 * @property string|null $event_type
 * @property array<array-key, mixed>|null $payload
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperBillingWebhookEvent
 */
class BillingWebhookEvent extends Model
{
    protected $fillable = [
        'source',
        'event_type',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
