<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Event;
use App\Models\SeatAssignment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class EventSeatAssignments extends Component
{
    public Event $event;

    /** @var array<int, int|string> guest_id => event_table_id (empty string = no table) */
    public array $assignments = [];

    public function mount(Event $event): void
    {
        $this->event = $event;
        foreach ($event->guests as $guest) {
            $a = $event->seatAssignments()->where('guest_id', $guest->id)->first();
            $this->assignments[$guest->id] = $a?->event_table_id ?? '';
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->event);

        foreach ($this->event->guests as $guest) {
            $tableId = $this->assignments[$guest->id] ?? '';
            if (! $tableId) {
                $this->event->seatAssignments()->where('guest_id', $guest->id)->delete();

                continue;
            }
            if (! $this->event->eventTables()->where('id', $tableId)->exists()) {
                continue;
            }
            SeatAssignment::updateOrCreate(
                ['event_id' => $this->event->id, 'guest_id' => $guest->id],
                ['event_table_id' => (int) $tableId, 'seat_number' => null]
            );
        }
    }

    public function render(): View
    {
        $this->authorize('view', $this->event);
        $guests = $this->event->guests()->orderBy('sort_order')->orderBy('name')->get();
        $tables = $this->event->eventTables()->orderBy('sort_order')->get();

        return view('livewire.dashboard.event-seat-assignments', [
            'guests' => $guests,
            'tables' => $tables,
        ]);
    }
}
