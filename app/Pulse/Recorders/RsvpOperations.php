<?php

declare(strict_types=1);

namespace App\Pulse\Recorders;

use Illuminate\Contracts\Config\Repository;
use Laravel\Pulse\Recorders\Recorder;
use Laravel\Pulse\Redis;

/**
 * Recorder for RSVP-related operations.
 *
 * Tracks:
 * - RSVP responses (attending, declining, maybe)
 * - Invitation sends
 * - Seating assignments
 */
final class RsvpOperations extends Recorder
{
    /**
     * The events to listen for.
     *
     * @var array<int, string>
     */
    public array $listen = [
        'rsvp.response.created',
        'invitation.sent',
        'seating.assigned',
    ];

    /**
     * Create a new recorder instance.
     */
    public function __construct(
        protected Repository $config,
        protected Redis $redis,
    ) {
        parent::__construct($config, $redis);
    }

    /**
     * Record the event.
     */
    public function record(string $event, array $data): void
    {
        match ($event) {
            'rsvp.response.created' => $this->recordRsvpResponse($data),
            'invitation.sent' => $this->recordInvitationSent($data),
            'seating.assigned' => $this->recordSeatingAssignment($data),
            default => null,
        };
    }

    /**
     * Record RSVP response.
     */
    private function recordRsvpResponse(array $data): void
    {
        $this->redis->pipeline(function ($redis) use ($data) {
            $key = 'rsvp_operations:'.now()->format('Y-m-d-H');
            $bucket = now()->format('Y-m-d-H:i');

            $redis->hincrby($key, $bucket.':'.$data['response_type'], 1);
            $redis->hincrby($key, $bucket.':total', 1);
            $redis->expire($key, $this->config->get('pulse.ingest.trim.keep', 7 * 24 * 60 * 60));
        });
    }

    /**
     * Record invitation sent.
     */
    private function recordInvitationSent(array $data): void
    {
        $this->redis->pipeline(function ($redis) use ($data) {
            $key = 'invitation_operations:'.now()->format('Y-m-d-H');

            $redis->hincrby($key, now()->format('Y-m-d-H:i').':sent', 1);
            $redis->hincrby($key, now()->format('Y-m-d-H:i').':org:'.$data['organization_id'], 1);
            $redis->expire($key, $this->config->get('pulse.ingest.trim.keep', 7 * 24 * 60 * 60));
        });
    }

    /**
     * Record seating assignment.
     */
    private function recordSeatingAssignment(array $data): void
    {
        $this->redis->pipeline(function ($redis) use ($data) {
            $key = 'seating_operations:'.now()->format('Y-m-d-H');

            $redis->hincrby($key, now()->format('Y-m-d-H:i').':assigned', 1);
            $redis->hincrby($key, now()->format('Y-m-d-H:i').':event:'.$data['event_id'], 1);
            $redis->expire($key, $this->config->get('pulse.ingest.trim.keep', 7 * 24 * 60 * 60));
        });
    }
}
