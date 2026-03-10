<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventTableController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $tables = $event->eventTables()->orderBy('sort_order')->get();

        return response()->json($tables);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $table = $event->eventTables()->create($validated);

        return response()->json($table, 201);
    }

    public function show(EventTable $eventTable): JsonResponse
    {
        Gate::authorize('view', $eventTable->event);

        return response()->json($eventTable);
    }

    public function update(Request $request, EventTable $eventTable): JsonResponse
    {
        Gate::authorize('update', $eventTable->event);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $eventTable->update($validated);

        return response()->json($eventTable);
    }

    public function destroy(EventTable $eventTable): JsonResponse
    {
        Gate::authorize('update', $eventTable->event);

        $eventTable->delete();

        return response()->json(null, 204);
    }
}
