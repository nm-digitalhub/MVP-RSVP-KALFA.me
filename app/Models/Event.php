<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $event_date
 * @property string|null $venue_name
 * @property array<array-key, mixed>|null $settings
 * @property EventStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\EventBilling|null $eventBilling
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventTable> $eventTables
 * @property-read int|null $event_tables_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Guest> $guests
 * @property-read int|null $guests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatAssignment> $seatAssignments
 * @property-read int|null $seat_assignments_count
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEventDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereVenueName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 * @mixin \Eloquent
 * @mixin IdeHelperEvent
 */
class Event extends Model implements HasMedia
{
    use HasFactory;
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

    public ?string $imageUrl {
        get {
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
    }

    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $customFields {
        get => $this->settings['custom'] ?? [];
    }

    public static function generateUniqueSlug(int $organizationId, string $name, ?int $ignoreEventId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'event';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::withTrashed()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->when($ignoreEventId !== null, fn ($query) => $query->whereKeyNot($ignoreEventId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function accountHasBillingAccess(): bool
    {
        $this->loadMissing('organization.account');

        return $this->organization?->account?->hasBillingAccess() ?? false;
    }

    public function requiresPerEventPayment(): bool
    {
        return ! $this->accountHasBillingAccess();
    }

    public function shouldActivateFromAccountBilling(): bool
    {
        if ($this->requiresPerEventPayment()) {
            return false;
        }

        if (! in_array($this->status, [EventStatus::Draft, EventStatus::PendingPayment], true)) {
            return false;
        }

        $this->loadMissing('eventBilling');

        return $this->eventBilling === null;
    }

    public function ensureAccessibleStatus(): bool
    {
        if (! $this->shouldActivateFromAccountBilling()) {
            return false;
        }

        $this->update(['status' => EventStatus::Active]);

        return true;
    }

    /**
     * Get badge color for Flux UI component.
     */
    public function getBadgeColor(): string
    {
        return match ($this->status) {
            EventStatus::Draft, EventStatus::Archived => 'neutral',
            EventStatus::PendingPayment => 'warning',
            EventStatus::Active => 'success',
            EventStatus::Locked => 'info',
            EventStatus::Cancelled => 'danger',
        };
    }
}
