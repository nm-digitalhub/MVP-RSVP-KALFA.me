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
 * Card showing RSVP response rates (attending, declining, maybe).
 */
#[Lazy]
class RsvpResponseRates extends Card
{
    /**
     * Render the card.
     */
    public function render(): Renderable
    {
        [$rsvpData, $time, $runAt] = $this->remember(
            fn () => $this->aggregateRsvpData(),
            'rsvp-response-rates'
        );

        return View::make('pulse.cards.rsvp-response-rates', [
            'time' => $time,
            'runAt' => $runAt,
            'rsvpData' => $rsvpData,
        ]);
    }

    /**
     * Aggregate RSVP response data from Pulse.
     *
     * @return Collection<string, int>
     */
    private function aggregateRsvpData(): Collection
    {
        // Get RSVP response counts by type
        $responseCounts = $this->aggregate(
            'rsvp_response',
            'count',
            orderBy: 'key',
            limit: 10,
        );

        // Transform into format expected by view
        return $responseCounts->mapWithKeys(fn ($item) => [
            str_replace('response:', '', $item->key) => (int) $item->count,
        ]);
    }
}
