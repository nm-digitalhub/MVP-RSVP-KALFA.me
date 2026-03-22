<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\InvitationStatus;
use App\Http\Requests\Api\StoreRsvpResponseRequest;
use App\Models\Invitation;
use App\Models\RsvpResponse;
use App\Services\EventLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Public RSVP page by invitation slug. Renders form; POST stores via same logic as API.
 */
class PublicRsvpViewController extends Controller
{
    public function show(string $slug): View|Response
    {
        $invitation = Invitation::where('slug', $slug)->with(['event', 'guest'])->firstOrFail();

        if ($invitation->event->ensureAccessibleStatus()) {
            $invitation->event->refresh();
        }

        if ($invitation->event->status !== EventStatus::Active) {
            return response()->view('rsvp.event-not-available', [], 404);
        }

        return view('rsvp.show', [
            'invitation' => $invitation,
            'eventLinks' => app(EventLinks::class),
        ]);
    }

    public function store(StoreRsvpResponseRequest $request, string $slug): RedirectResponse|Response
    {
        $invitation = Invitation::where('slug', $slug)->with('event')->firstOrFail();

        if ($invitation->event->ensureAccessibleStatus()) {
            $invitation->event->refresh();
        }

        if ($invitation->event->status !== EventStatus::Active) {
            return response()->view('rsvp.event-not-available', [], 404);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($invitation, $validated, $request): void {
            RsvpResponse::updateOrCreate(
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
                'status' => InvitationStatus::Responded,
                'responded_at' => now(),
            ]);
        });

        return redirect()->route('rsvp.show', $slug)->with('success', true);
    }
}
