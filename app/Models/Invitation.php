<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
            'status' => InvitationStatus::class,
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
