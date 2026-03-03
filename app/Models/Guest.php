<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'group_name',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function invitation(): HasOne
    {
        return $this->hasOne(Invitation::class, 'guest_id');
    }

    public function rsvpResponses(): HasMany
    {
        return $this->hasMany(RsvpResponse::class, 'guest_id');
    }

    public function seatAssignment(): HasOne
    {
        return $this->hasOne(SeatAssignment::class, 'guest_id');
    }
}
