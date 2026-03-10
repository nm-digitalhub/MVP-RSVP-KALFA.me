<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGuestRequest;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GuestController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $guests = $event->guests()->orderBy('sort_order')->get();

        return response()->json($guests);
    }

    public function store(StoreGuestRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $guest = $event->guests()->create($request->validated());

        return response()->json($guest, 201);
    }

    public function show(Guest $guest): JsonResponse
    {
        Gate::authorize('view', $guest);

        return response()->json($guest);
    }

    public function update(Request $request, Guest $guest): JsonResponse
    {
        Gate::authorize('update', $guest);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $guest->update($validated);

        return response()->json($guest);
    }

    public function destroy(Guest $guest): JsonResponse
    {
        Gate::authorize('delete', $guest);

        $guest->delete();

        return response()->json(null, 204);
    }
}
