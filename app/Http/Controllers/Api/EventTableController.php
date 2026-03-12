<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventTableController extends Controller
{
    /**
     * List all seating tables for an event, ordered by sort_order.
     */
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $tables = $event->eventTables()->orderBy('sort_order')->get();

        return response()->json($tables);
    }

    /**
     * Create a new seating table/area for an event.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $table = $event->eventTables()->create($validated);

        return response()->json($table, 201);
    }

    /**
     * Get a single seating table.
     */
    public function show(EventTable $eventTable): JsonResponse
    {
        $this->authorize('view', $eventTable->event);

        return response()->json($eventTable);
    }

    /**
     * Update a seating table's name, capacity, or sort order.
     */
    public function update(Request $request, EventTable $eventTable): JsonResponse
    {
        $this->authorize('update', $eventTable->event);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $eventTable->update($validated);

        return response()->json($eventTable);
    }

    /**
     * Delete a seating table.
     */
    public function destroy(EventTable $eventTable): JsonResponse
    {
        $this->authorize('update', $eventTable->event);

        $eventTable->delete();

        return response()->json(null, 204);
    }
}
