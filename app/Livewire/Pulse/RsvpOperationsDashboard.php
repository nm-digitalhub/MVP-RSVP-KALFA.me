<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Attributes\Lazy;

/**
 * Comprehensive dashboard card showing all RSVP operations.
 *
 * Displays:
 * - RSVP responses by type
 * - Invitations sent (total and by organization)
 * - Seating assignments (total and by event)
 */
#[Lazy]
class RsvpOperationsDashboard extends Card
{
    /**
     * Render the card.
     */
    public function render(): Renderable
    {
        [$data, $time, $runAt] = $this->remember(
            fn () => $this->aggregateAllRsvpData(),
            'rsvp-operations-dashboard'
        );

        return View::make('pulse.cards.rsvp-operations-dashboard', [
            'time' => $time,
            'runAt' => $runAt,
            'data' => $data,
        ]);
    }

    /**
     * Aggregate all RSVP operations data.
     */
    private function aggregateAllRsvpData(): array
    {
        return [
            'responses' => $this->getResponseData(),
            'invitations' => $this->getInvitationData(),
            'seating' => $this->getSeatingData(),
        ];
    }

    /**
     * Get RSVP response statistics.
     */
    private function getResponseData(): array
    {
        $counts = $this->aggregate(
            'rsvp_response',
            'count',
            orderBy: 'key',
            limit: 10,
        );

        return [
            'by_type' => $counts->mapWithKeys(fn ($item) => [
                str_replace('response:', '', $item->key) => (int) $item->count,
            ])->toArray(),
            'top_events' => $this->aggregate(
                'rsvp_response_by_event',
                'count',
                orderBy: 'count',
                limit: 5,
            )->map(fn ($item) => [
                'event' => str_replace('event:', '', explode(':', $item->key)[0] ?? $item->key),
                'count' => (int) $item->count,
            ])->toArray(),
        ];
    }

    /**
     * Get invitation statistics.
     */
    private function getInvitationData(): array
    {
        $total = $this->aggregateTotal('invitation_sent', 'count');

        return [
            'total' => (int) $total,
            'by_org' => $this->aggregate(
                'invitation_sent_by_org',
                'count',
                orderBy: 'count',
                limit: 5,
            )->map(fn ($item) => [
                'org' => str_replace('org:', '', $item->key),
                'count' => (int) $item->count,
            ])->toArray(),
            'by_event' => $this->aggregate(
                'invitation_sent_by_event',
                'count',
                orderBy: 'count',
                limit: 5,
            )->map(fn ($item) => [
                'event' => str_replace('event:', '', $item->key),
                'count' => (int) $item->count,
            ])->toArray(),
        ];
    }

    /**
     * Get seating assignment statistics.
     */
    private function getSeatingData(): array
    {
        $total = $this->aggregateTotal('seating_assignment', 'count');

        return [
            'total' => (int) $total,
            'by_event' => $this->aggregate(
                'seating_assignment_by_event',
                'count',
                orderBy: 'count',
                limit: 5,
            )->map(fn ($item) => [
                'event' => str_replace('event:', '', $item->key),
                'count' => (int) $item->count,
            ])->toArray(),
            'by_table' => $this->aggregate(
                'seating_assignment_by_table',
                'count',
                orderBy: 'count',
                limit: 10,
            )->map(fn ($item) => [
                'table' => str_replace('event:', '', $item->key),
                'count' => (int) $item->count,
            ])->toArray(),
        ];
    }
}
