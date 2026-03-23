<?php

declare(strict_types=1);

namespace App\Pulse\Cards;

use Illuminate\Http\Request;
use Laravel\Pulse\Card;
use Laravel\Pulse\Cards\GaugeCard;
use Laravel\Pulse\Pulse;

/**
 * Card showing RSVP response rates (attending, declining, maybe).
 */
final class RsvpResponseRates extends Card
{
    /**
     * Render the card.
     */
    public function render(Request $request): string
    {
        $cards = [];

        $responses = ['attending', 'declining', 'maybe'];

        foreach ($responses as $response) {
            $count = $this->getRsvpCount($response);

            $cards[] = (new GaugeCard(
                'rsvp_responses_'.$response,
                ucfirst($response),
                $count,
                'RSVP Responses',
                now()->subHours(24)->toImmutable(),
                now()->toImmutable(),
            ))->render($request);
        }

        return implode('', $cards);
    }

    /**
     * Get RSVP count for specific response type.
     */
    private function getRsvpCount(string $response): int
    {
        $counts = Pulse::aggregate(
            'rsvp_operations',
            ['sum', 'count'],
            now()->subHours(24)->toImmutable(),
            now()->toImmutable(),
            'response_type',
        );

        return (int) ($counts[$response] ?? 0);
    }
}
