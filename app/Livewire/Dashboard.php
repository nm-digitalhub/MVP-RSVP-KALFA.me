<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): mixed
    {
        $organization = auth()->user()->currentOrganization();

        if ($organization === null) {
            return $this->redirect(route('organizations.index'), navigate: true);
        }

        return null;
    }

    public function render()
    {
        $organization = auth()->user()->currentOrganization();
        $events = $organization
            ? $organization->events()->withCount('guests')->orderByDesc('event_date')->get()
            : collect();

        $totalEvents = $events->count();
        $totalGuests = $events->sum('guests_count');
        $upcomingEvent = $organization
            ? $organization->events()->where('event_date', '>=', now()->startOfDay())->orderBy('event_date')->first()
            : null;
        $organizationStatusBadge = $organization ? 'active' : null;

        return view('livewire.dashboard', [
            'organization' => $organization,
            'events' => $events,
            'totalEvents' => $totalEvents,
            'totalGuests' => $totalGuests,
            'upcomingEvent' => $upcomingEvent,
            'organizationStatusBadge' => $organizationStatusBadge,
        ]);
    }
}
