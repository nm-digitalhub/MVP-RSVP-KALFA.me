<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $event_id
 * @property int|null $guest_id
 * @property string $token
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property InvitationStatus $status
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event|null $event
 * @property-read \App\Models\Guest|null $guest
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RsvpResponse> $rsvpResponses
 * @property-read int|null $rsvp_responses_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereGuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperInvitation
 */
class Invitation extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'token',
        'slug',
        'expires_at',
        'status',
        'responded_at',
        'call_sid',
        'call_status',
        'call_duration',
        'call_initiated_at',
        'call_ended_at',
        'call_metadata',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
            'status' => InvitationStatus::class,
            'call_duration' => 'integer',
            'call_initiated_at' => 'datetime',
            'call_ended_at' => 'datetime',
            'call_metadata' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function rsvpResponses(): HasMany
    {
        return $this->hasMany(RsvpResponse::class, 'invitation_id');
    }
}
