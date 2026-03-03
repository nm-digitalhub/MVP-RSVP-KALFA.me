<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'event_date',
        'venue_name',
        'settings',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'settings' => 'array',
            'status' => EventStatus::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'event_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'event_id');
    }

    public function eventTables(): HasMany
    {
        return $this->hasMany(EventTable::class, 'event_id');
    }

    public function seatAssignments(): HasMany
    {
        return $this->hasMany(SeatAssignment::class, 'event_id');
    }

    public function eventBilling(): HasOne
    {
        return $this->hasOne(EventBilling::class, 'event_id');
    }
}
