<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_id
 * @property int $guest_id
 * @property int $event_table_id
 * @property string|null $seat_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event|null $event
 * @property-read \App\Models\EventTable|null $eventTable
 * @property-read \App\Models\Guest|null $guest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereEventTableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereGuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereSeatNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperSeatAssignment
 */
class SeatAssignment extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'event_table_id',
        'seat_number',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function eventTable(): BelongsTo
    {
        return $this->belongsTo(EventTable::class, 'event_table_id');
    }
}
