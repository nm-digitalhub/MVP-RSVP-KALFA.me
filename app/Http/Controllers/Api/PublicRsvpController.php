<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRsvpResponseRequest;
use App\Models\Invitation;
use App\Models\RsvpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicRsvpController extends Controller
{
    /**
     * Get invitation details by slug.
     *
     * Returns public-safe event and guest information for an invitation link.
     * Event must be in Active status.
     *
     * @unauthenticated
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $invitation = Invitation::where('slug', $slug)->with(['event', 'guest'])->firstOrFail();

        $event = $invitation->event;
        if ($event->ensureAccessibleStatus()) {
            $event->refresh();
        }

        if ($event->status !== EventStatus::Active) {
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
     * Submit or update RSVP response.
     *
     * Idempotent per invitation: calling again with a different response will update the existing one.
     * Only available for events in Active status.
     *
     * @unauthenticated
     */
    public function storeResponse(StoreRsvpResponseRequest $request, string $slug): JsonResponse
    {
        $invitation = Invitation::where('slug', $slug)->with('event')->firstOrFail();

        if ($invitation->event->ensureAccessibleStatus()) {
            $invitation->event->refresh();
        }

        if ($invitation->event->status !== EventStatus::Active) {
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
                    'response_method' => 'web',
                ])
            );
            $invitation->update([
                'status' => InvitationStatus::Responded,
                'responded_at' => now(),
            ]);

            // Dispatch Pulse tracking event
            \Illuminate\Support\Facades\Event::dispatch('rsvp.response.created', [
                'response_type' => $rsvp->response->value,
                'event_id' => $invitation->event_id,
                'guest_id' => $invitation->guest_id,
            ]);

            return $rsvp;
        });

        return response()->json([
            'success' => true,
            'response' => $rsvp->response->value ?? null,
        ], 201);
    }
}
