<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTable extends Model
{
    use SoftDeletes;

    protected $table = 'event_tables';

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
