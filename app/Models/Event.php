<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Event extends Model implements HasMedia
{
    use InteractsWithMedia;
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('event-image')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('hero')
            ->width(1600)
            ->height(900)
            ->sharpen(10);

        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(225);
    }

    public function imageUrl(): ?string
    {
        $url = $this->getFirstMediaUrl('event-image', 'hero');
        if ($url !== '') {
            return $url;
        }

        $path = $this->settings['image_path'] ?? null;
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function customFields(): array
    {
        return $this->settings['custom'] ?? [];
    }
}
