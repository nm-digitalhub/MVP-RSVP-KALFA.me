<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEventRequest;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Services\UsageMeter;
use App\Services\UsagePolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function __construct(
        private UsagePolicyService $usagePolicy,
        private UsageMeter $usageMeter,
    ) {}

    /**
     * List events for an organization.
     *
     * Supports optional `?status` query filter (e.g. `Draft`, `Active`, `PendingPayment`, `Cancelled`, `Completed`).
     * Returns a paginated result.
     */
    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', [Event::class, $organization->id]);

        $events = Event::where('organization_id', $organization->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('event_date')
            ->paginate();

        return response()->json($events);
    }

    /**
     * Create a new event.
     *
     * Event is activated immediately when the organization already has billing access.
     * Usage limits are enforced before creation.
     */
    public function store(StoreEventRequest $request, Organization $organization): JsonResponse
    {
        Gate::authorize('create', [Event::class, $organization->id]);

        $account = $organization->account;

        // Check usage limits before creating the event (only if account exists)
        if ($account !== null) {
            $decision = $this->usagePolicy->check($account, 'max_active_events', 1);

            if ($decision->isBlocked()) {
                return response()->json([
                    'error' => 'usage_limit_exceeded',
                    'message' => 'You have reached your plan limit for active events. Upgrade your plan to create more events.',
                    'metric' => 'max_active_events',
                ], 422);
            }
        }

        $event = Event::create(array_merge($request->validated(), [
            'organization_id' => $organization->id,
            'status' => $organization->account?->hasBillingAccess() ? EventStatus::Active : EventStatus::Draft,
        ]));

        // Record usage for the created event (only for active events with account/product)
        if ($event->status === EventStatus::Active && $account?->product) {
            $this->usageMeter->record(
                $account,
                $account->product,
                'max_active_events',
                1,
                metadata: ['event_id' => $event->id, 'event_name' => $event->name],
            );
        }

        return response()->json($event, 201);
    }

    /**
     * Get a single event with guests, tables, invitations, and billing details.
     */
    public function show(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        if ($event->ensureAccessibleStatus()) {
            $event->refresh();
        }

        $event->load(['guests', 'eventTables', 'invitations', 'eventBilling', 'organization.account']);

        return response()->json($event);
    }

    /**
     * Update an event's details.
     */
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $event->update($request->validated());

        return response()->json($event);
    }

    /**
     * Soft-delete an event.
     */
    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return response()->json(null, 204);
    }
}
