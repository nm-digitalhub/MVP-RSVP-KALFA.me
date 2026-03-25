<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RsvpResponseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invitation_id
 * @property int|null $guest_id
 * @property RsvpResponseType $response
 * @property int|null $attendees_count
 * @property string|null $message
 * @property string|null $ip
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Guest|null $guest
 * @property-read \App\Models\Invitation $invitation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereAttendeesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereGuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereInvitationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereUserAgent($value)
 * @mixin \Eloquent
 * @mixin IdeHelperRsvpResponse
 */
class RsvpResponse extends Model
{
    protected $fillable = [
        'invitation_id',
        'guest_id',
        'response',
        'attendees_count',
        'message',
        'ip',
        'user_agent',
        'response_method',
        'call_sid',
    ];

    protected function casts(): array
    {
        return [
            'response' => RsvpResponseType::class,
            'attendees_count' => 'integer',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
