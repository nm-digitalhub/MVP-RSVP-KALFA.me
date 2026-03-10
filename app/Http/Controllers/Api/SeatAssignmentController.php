<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\SeatAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatAssignmentController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $assignments = $event->seatAssignments()->with(['guest', 'eventTable'])->get();

        return response()->json($assignments);
    }

    /**
     * Bulk update: replace or patch seat assignments for the event.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $validated = $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.guest_id' => ['required', 'exists:guests,id'],
            'assignments.*.event_table_id' => ['required', 'exists:event_tables,id'],
            'assignments.*.seat_number' => ['nullable', 'string', 'max:50'],
        ]);

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
