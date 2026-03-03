<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RsvpResponseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RsvpResponse extends Model
{
    protected $table = 'rsvp_responses';

    protected $fillable = [
        'invitation_id',
        'guest_id',
        'response',
        'attendees_count',
        'message',
        'ip',
        'user_agent',
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
