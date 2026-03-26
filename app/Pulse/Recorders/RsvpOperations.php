<?php

declare(strict_types=1);

namespace App\Pulse\Recorders;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Laravel\Pulse\Concerns\ConfiguresAfterResolving;
use Laravel\Pulse\Pulse;

/**
 * Recorder for RSVP-related operations.
 *
 * Tracks:
 * - RSVP responses (attending, declining, maybe)
 * - Invitation sends
 * - Seating assignments
 */
final class RsvpOperations
{
    use ConfiguresAfterResolving;

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
        protected Pulse $pulse,
    ) {
        //
    }

    /**
     * Register the recorder.
     */
    public function register(callable $record, Application $app): void
    {
        $this->afterResolving($app, Dispatcher::class, function (Dispatcher $events) use ($record) {
            foreach ($this->listen as $event) {
                $events->listen($event, fn (...$data) => $record($event, ...$data));
            }
        });
    }

    /**
     * Record the event.
     */
    public function record(string $event, array $data): void
    {
        $timestamp = CarbonImmutable::now()->getTimestamp();

        match ($event) {
            'rsvp.response.created' => $this->recordRsvpResponse($timestamp, $data),
            'invitation.sent' => $this->recordInvitationSent($timestamp, $data),
            'seating.assigned' => $this->recordSeatingAssignment($timestamp, $data),
            default => null,
        };
    }

    /**
     * Record RSVP response.
     */
    private function recordRsvpResponse(int $timestamp, array $data): void
    {
        $responseType = $data['response_type'] ?? 'unknown';
        $eventId = $data['event_id'] ?? 'unknown';

        // Record response type count
        $this->pulse->record(
            type: 'rsvp_response',
            key: 'response:'.$responseType,
            value: 1,
            timestamp: $timestamp,
        )->count()->onlyBuckets();

        // Record per-event RSVP count
        $this->pulse->record(
            type: 'rsvp_response_by_event',
            key: 'event:'.$eventId.':'.$responseType,
            value: 1,
            timestamp: $timestamp,
        )->count()->onlyBuckets();
    }

    /**
     * Record invitation sent.
     */
    private function recordInvitationSent(int $timestamp, array $data): void
    {
        $organizationId = $data['organization_id'] ?? 'unknown';
        $eventId = $data['event_id'] ?? 'unknown';

        // Record total invitations sent
        $this->pulse->record(
            type: 'invitation_sent',
            key: 'total',
            value: 1,
            timestamp: $timestamp,
        )->count()->onlyBuckets();

        // Record per-organization invitations
        $this->pulse->record(
            type: 'invitation_sent_by_org',
            key: 'org:'.$organizationId,
            value: 1,
            timestamp: $timestamp,
        )->count()->onlyBuckets();

        // Record per-event invitations
        $this->pulse->record(
            type: 'invitation_sent_by_event',
            key: 'event:'.$eventId,
            value: 1,
            timestamp: $timestamp,
        )->count()->onlyBuckets();
    }

    /**
     * Record seating assignment.
     */
    private function recordSeatingAssignment(int $timestamp, array $data): void
    {
        $eventId = $data['event_id'] ?? 'unknown';
        $tableId = $data['table_id'] ?? 'unknown';

        // Record total seat assignments
        $this->pulse->record(
            type: 'seating_assignment',
            key: 'total',
            value: 1,
            timestamp: $timestamp,
        )->count()->onlyBuckets();

        // Record per-event seat assignments
        $this->pulse->record(
            type: 'seating_assignment_by_event',
            key: 'event:'.$eventId,
            value: 1,
            timestamp: $timestamp,
        )->count()->onlyBuckets();

        // Record per-table assignments
        $this->pulse->record(
            type: 'seating_assignment_by_table',
            key: 'event:'.$eventId.':table:'.$tableId,
            value: 1,
            timestamp: $timestamp,
        )->count()->onlyBuckets();
    }
}
