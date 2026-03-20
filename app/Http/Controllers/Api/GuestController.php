<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Api\UpdateGuestRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGuestRequest;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;

class GuestController extends Controller
{
    /**
     * List all guests for an event, ordered by sort_order.
     */
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $guests = $event->guests()->orderBy('sort_order')->get();

        return response()->json($guests);
    }

    /**
     * Add a guest to an event.
     */
    public function store(StoreGuestRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $guest = $event->guests()->create($request->validated());

        return response()->json($guest, 201);
    }

    /**
     * Get a single guest record.
     */
    public function show(Guest $guest): JsonResponse
    {
        Gate::authorize('view', $guest);

        return response()->json($guest);
    }

    /**
     * Update a guest. All fields are optional (PATCH semantics).
     */
    public function update(UpdateGuestRequest $request, Guest $guest): JsonResponse
    {
        Gate::authorize('update', $guest);

        $validated = $request->validated();

        $guest->update($validated);

        return response()->json($guest);
    }

    /**
     * Remove a guest from an event.
     */
    public function destroy(Guest $guest): JsonResponse
    {
        Gate::authorize('delete', $guest);

        $guest->delete();

        return response()->json(null, 204);
    }
}
