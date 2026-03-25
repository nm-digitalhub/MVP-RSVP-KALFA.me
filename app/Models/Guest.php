<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $group_name
 * @property string|null $notes
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Event|null $event
 * @property-read \App\Models\Invitation|null $invitation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RsvpResponse> $rsvpResponses
 * @property-read int|null $rsvp_responses_count
 * @property-read \App\Models\SeatAssignment|null $seatAssignment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereGroupName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest withoutTrashed()
 * @mixin \Eloquent
 * @mixin IdeHelperGuest
 */
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
