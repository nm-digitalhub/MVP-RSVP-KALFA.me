<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateSeatAssignmentRequest;
use App\Models\Event;
use App\Models\SeatAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SeatAssignmentController extends Controller
{
    /**
     * List all seat assignments for an event, with guest and table details.
     */
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $assignments = $event->seatAssignments()->with(['guest', 'eventTable'])->get();

        return response()->json($assignments);
    }

    /**
     * Bulk upsert seat assignments for an event.
     *
     * Replaces or creates assignments for the given guests. Existing assignments not in the list are preserved.
     * Returns the full updated list.
     */
    public function update(UpdateSeatAssignmentRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $validated = $request->validated();

        foreach ($validated['assignments'] as $item) {
            SeatAssignment::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'guest_id' => $item['guest_id'],
                ],
                [
                    'event_table_id' => $item['event_table_id'],
                    'seat_number' => $item['seat_number'] ?? null,
                ]
            );
        }

        $assignments = $event->seatAssignments()->with(['guest', 'eventTable'])->get();

        return response()->json($assignments);
    }
}
