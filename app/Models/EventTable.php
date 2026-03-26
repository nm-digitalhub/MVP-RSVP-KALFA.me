<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property int $capacity
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Event|null $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatAssignment> $seatAssignments
 * @property-read int|null $seat_assignments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable withoutTrashed()
 * @mixin \Eloquent
 * @mixin IdeHelperEventTable
 */
class EventTable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'capacity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function seatAssignments(): HasMany
    {
        return $this->hasMany(SeatAssignment::class, 'event_table_id');
    }
}
