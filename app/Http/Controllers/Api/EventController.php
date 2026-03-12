<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEventRequest;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * List events for an organization.
     *
     * Supports optional `?status` query filter (e.g. `Draft`, `Active`, `PendingPayment`, `Cancelled`, `Completed`).
     * Returns a paginated result.
     */
    public function index(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('viewAny', [Event::class, $organization->id]);

        $events = Event::where('organization_id', $organization->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('event_date')
            ->paginate();

        return response()->json($events);
    }

    /**
     * Create a new event.
     *
     * Event is created in `Draft` status. Payment must be initiated to activate it.
     */
    public function store(StoreEventRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('create', [Event::class, $organization->id]);

        $event = Event::create(array_merge($request->validated(), [
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
        ]));

        return response()->json($event, 201);
    }

    /**
     * Get a single event with guests, tables, invitations, and billing details.
     */
    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $event->load(['guests', 'eventTables', 'invitations', 'eventBilling']);

        return response()->json($event);
    }

    /**
     * Update an event's details.
     */
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->update($request->validated());

        return response()->json($event);
    }

    /**
     * Soft-delete an event.
     */
    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return response()->json(null, 204);
    }
}
