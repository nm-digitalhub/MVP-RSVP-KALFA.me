<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $invitations = $event->invitations()->with('guest')->get();

        return response()->json($invitations);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'guest_id' => ['nullable', 'exists:guests,id'],
        ]);

        $invitation = $event->invitations()->create([
            'guest_id' => $validated['guest_id'] ?? null,
            'token' => Str::random(64),
            'slug' => Str::slug(Str::random(12) . '-' . now()->timestamp),
            'status' => InvitationStatus::Pending,
        ]);

        return response()->json($invitation, 201);
    }

    /**
     * Placeholder: send invitation (e.g. email link). No mail driver in MVP.
     */
    public function send(Event $event, Invitation $invitation): JsonResponse
    {
        $this->authorize('update', $event);

        if ($invitation->event_id !== $event->id) {
            abort(404);
        }

        $invitation->update(['status' => InvitationStatus::Sent]);

        return response()->json([
            'message' => 'Invitation marked as sent. Configure mail driver to send link.',
            'invitation' => $invitation->fresh(),
        ]);
    }
}
