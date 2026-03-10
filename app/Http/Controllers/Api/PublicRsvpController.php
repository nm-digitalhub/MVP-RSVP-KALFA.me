<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRsvpResponseRequest;
use App\Models\Invitation;
use App\Models\RsvpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicRsvpController extends Controller
{
    /**
     * Get invitation by slug (public, no auth). Only public-safe fields.
     * Event must be active. Never expose billing, payment IDs, organization, or internal IDs.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $invitation = Invitation::where('slug', $slug)->with(['event', 'guest'])->firstOrFail();

        $event = $invitation->event;
        if ($event->status !== \App\Enums\EventStatus::Active) {
            return response()->json(['message' => 'Event not available.'], 404);
        }

        return response()->json([
            'slug' => $invitation->slug,
            'event_name' => $event->name,
            'event_date' => $event->event_date?->toDateString(),
            'venue_name' => $event->venue_name,
            'guest_name' => $invitation->guest?->name,
        ]);
    }

    /**
     * Submit or update RSVP response (public, idempotent per invitation).
     * Only for active events. Response contains only public-safe fields.
     */
    public function storeResponse(StoreRsvpResponseRequest $request, string $slug): JsonResponse
    {
        $invitation = Invitation::where('slug', $slug)->with('event')->firstOrFail();

        if ($invitation->event->status !== \App\Enums\EventStatus::Active) {
            return response()->json(['message' => 'Event not available.'], 404);
        }

        $validated = $request->validated();

        $rsvp = DB::transaction(function () use ($invitation, $validated, $request) {
            $rsvp = RsvpResponse::updateOrCreate(
                [
                    'invitation_id' => $invitation->id,
                    'guest_id' => $invitation->guest_id,
                ],
                array_merge($validated, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
            );
            $invitation->update([
                'status' => \App\Enums\InvitationStatus::Responded,
                'responded_at' => now(),
            ]);

            return $rsvp;
        });

        return response()->json([
            'success' => true,
            'response' => $rsvp->response->value ?? null,
        ], 201);
    }
}
