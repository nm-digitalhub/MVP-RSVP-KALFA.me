<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Gate;
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
    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', [Event::class, $organization->id]);

        $events = Event::where('organization_id', $organization->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('event_date')
            ->paginate();

        return response()->json($events);
    }

    public function store(StoreEventRequest $request, Organization $organization): JsonResponse
    {
        Gate::authorize('create', [Event::class, $organization->id]);

        $event = Event::create(array_merge($request->validated(), [
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
        ]));

        return response()->json($event, 201);
    }

    public function show(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $event->load(['guests', 'eventTables', 'invitations', 'eventBilling']);

        return response()->json($event);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $event->update($request->validated());

        return response()->json($event);
    }

    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return response()->json(null, 204);
    }
}
