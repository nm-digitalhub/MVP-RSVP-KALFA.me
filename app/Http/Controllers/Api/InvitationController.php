<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Api\StoreInvitationRequest;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Invitation;
use App\Services\WhatsAppRsvpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /**
     * List all invitations for an event with guest details.
     */
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $invitations = $event->invitations()->with('guest')->get();

        return response()->json($invitations);
    }

    /**
     * Create a new invitation for an event.
     *
     * Optionally link to a guest via `guest_id`. Generates a unique RSVP slug automatically.
     */
    public function store(StoreInvitationRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $validated = $request->validated();

        $invitation = $event->invitations()->create([
            'guest_id' => $validated['guest_id'] ?? null,
            'token' => Str::random(64),
            'slug' => Str::slug(Str::random(12).'-'.now()->timestamp),
            'status' => InvitationStatus::Pending,
        ]);

        return response()->json($invitation, 201);
    }

    /**
     * Send an invitation via WhatsApp (optional) and mark it as sent.
     *
     * Pass `send_whatsapp: true` to send the RSVP link via WhatsApp to the guest's phone number.
     */
    public function send(Request $request, Event $event, Invitation $invitation, WhatsAppRsvpService $whatsAppRsvp): JsonResponse
    {
        Gate::authorize('update', $event);

        if ($invitation->event_id !== $event->id) {
            abort(404);
        }

        $sendWhatsApp = $request->boolean('send_whatsapp');
        $whatsappResult = null;

        if ($sendWhatsApp) {
            $whatsappResult = $whatsAppRsvp->sendRsvpLink($invitation);
        }

        $invitation->update(['status' => InvitationStatus::Sent]);

        $payload = [
            'message' => 'Invitation marked as sent.',
            'invitation' => $invitation->fresh(),
        ];

        if ($sendWhatsApp && $whatsappResult !== null) {
            $payload['whatsapp'] = [
                'sent' => $whatsappResult['success'],
                'sid' => $whatsappResult['sid'] ?? null,
            ];
            if (! empty($whatsappResult['error'])) {
                $payload['whatsapp']['error'] = $whatsappResult['error'];
            }
        }

        return response()->json($payload);
    }
}
