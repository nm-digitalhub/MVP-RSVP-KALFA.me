<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\InvitationStatus;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

final class EventInvitations extends Component
{
    public Event $event;

    public ?int $createForGuestId = null;

    public function mount(Event $event): void
    {
        $event->ensureAccessibleStatus();

        $this->event = $event->fresh();
    }

    #[On('echo-private:event.{event.id},RsvpReceived')]
    public function onRsvpReceived(): void
    {
        // Refresh the list
    }

    public function createInvitation(): void
    {
        $this->authorize('update', $this->event);
        $this->validate(['createForGuestId' => 'nullable|exists:guests,id']);

        $this->event->invitations()->create([
            'guest_id' => $this->createForGuestId ?: null,
            'token' => Str::random(64),
            'slug' => Str::slug(Str::random(12).'-'.now()->timestamp),
            'status' => InvitationStatus::Pending,
        ]);
        $this->createForGuestId = null;
    }

    public function markSent(int $id): void
    {
        $invitation = $this->event->invitations()->find($id);
        if ($invitation === null) {
            return;
        }
        $this->authorize('update', $this->event);
        $invitation->update(['status' => InvitationStatus::Sent]);
    }

    public function render(): View
    {
        $this->authorize('view', $this->event);
        $invitations = $this->event->invitations()->with('guest')->orderBy('id')->get();
        $guestsWithoutInvitation = $this->event->guests()->whereDoesntHave('invitation')->orderBy('name')->get();

        return view('livewire.dashboard.event-invitations', [
            'invitations' => $invitations,
            'guestsWithoutInvitation' => $guestsWithoutInvitation,
        ]);
    }
}
