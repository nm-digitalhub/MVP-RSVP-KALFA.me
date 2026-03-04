<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class EventTables extends Component
{
    public Event $event;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $capacity = null;

    /** @var 'list'|'chart' List = sortable list; chart = seating chart / seating plan (visual grid) */
    public string $viewMode = 'list';

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function openCreate(): void
    {
        $this->authorize('update', $this->event);
        $this->editingId = null;
        $this->name = '';
        $this->capacity = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $table = $this->event->eventTables()->find($id);
        if ($table === null) {
            return;
        }
        $this->authorize('update', $this->event);
        $this->editingId = $table->id;
        $this->name = $table->name;
        $this->capacity = $table->capacity;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->event);
        $this->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:0',
        ]);

        if ($this->editingId !== null) {
            $table = $this->event->eventTables()->find($this->editingId);
            if ($table) {
                $table->update(['name' => $this->name, 'capacity' => $this->capacity]);
            }
        } else {
            $maxOrder = $this->event->eventTables()->max('sort_order') ?? -1;
            $this->event->eventTables()->create([
                'name' => $this->name,
                'capacity' => $this->capacity ?? 0,
                'sort_order' => $maxOrder + 1,
            ]);
        }
        $this->showForm = false;
        $this->editingId = null;
    }

    public function deleteTable(int $id): void
    {
        $table = $this->event->eventTables()->find($id);
        if ($table === null) {
            return;
        }
        $this->authorize('update', $this->event);
        $table->delete();
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    /**
     * Reorder tables: set moved item to new position and re-index sort_order for the event.
     */
    public function handleSort(int $id, int $newPosition): void
    {
        $this->authorize('update', $this->event);
        $tables = $this->event->eventTables()->orderBy('sort_order')->get();
        $item = $tables->firstWhere('id', $id);
        if ($item === null) {
            return;
        }
        $newPosition = max(0, min($newPosition, $tables->count() - 1));
        $ordered = $tables->values()->all();
        $fromIndex = (int) array_search($item->id, array_map(fn ($t) => $t->id, $ordered));
        array_splice($ordered, $fromIndex, 1);
        array_splice($ordered, $newPosition, 0, [$item]);
        foreach ($ordered as $i => $t) {
            $t->update(['sort_order' => $i]);
        }
    }

    public function render(): View
    {
        $this->authorize('view', $this->event);
        $tables = $this->event->eventTables()->orderBy('sort_order')->get();

        return view('livewire.dashboard.event-tables', ['tables' => $tables]);
    }
}
