<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
