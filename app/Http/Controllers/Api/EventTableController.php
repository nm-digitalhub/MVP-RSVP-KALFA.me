<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Api\UpdateEventTableRequest;
use App\Http\Requests\Api\StoreEventTableRequest;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTable;
use Illuminate\Http\JsonResponse;

class EventTableController extends Controller
{
    /**
     * List all seating tables for an event, ordered by sort_order.
     */
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $tables = $event->eventTables()->orderBy('sort_order')->get();

        return response()->json($tables);
    }

    /**
     * Create a new seating table/area for an event.
     */
    public function store(StoreEventTableRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $validated = $request->validated();

        $table = $event->eventTables()->create($validated);

        return response()->json($table, 201);
    }

    /**
     * Get a single seating table.
     */
    public function show(EventTable $eventTable): JsonResponse
    {
        Gate::authorize('view', $eventTable->event);

        return response()->json($eventTable);
    }

    /**
     * Update a seating table's name, capacity, or sort order.
     */
    public function update(UpdateEventTableRequest $request, EventTable $eventTable): JsonResponse
    {
        Gate::authorize('update', $eventTable->event);

        $validated = $request->validated();

        $eventTable->update($validated);

        return response()->json($eventTable);
    }

    /**
     * Delete a seating table.
     */
    public function destroy(EventTable $eventTable): JsonResponse
    {
        Gate::authorize('update', $eventTable->event);

        $eventTable->delete();

        return response()->json(null, 204);
    }
}
