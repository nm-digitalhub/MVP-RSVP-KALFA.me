<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreEventRequest;
use App\Http\Requests\Dashboard\UpdateEventRequest;
use App\Models\Event;
use App\Services\EventLinks;
use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Event management (dashboard). Policy-protected.
 */
class EventController extends Controller
{
    public function __construct(
        private OrganizationContext $context
    ) {}

    public function create(): View|RedirectResponse
    {
        $organization = $this->context->current();
        if ($organization === null) {
            return redirect()->route('organizations.index');
        }
        Gate::authorize('create', [Event::class, $organization->id]);

        return view('dashboard.events.create', [
            'organization' => $organization,
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $organization = $this->context->current();
        if ($organization === null) {
            return redirect()->route('organizations.index');
        }
        Gate::authorize('create', [Event::class, $organization->id]);

        $validated = $request->validated();
        $settings = [];

        if (array_key_exists('description', $validated) && $validated['description'] !== null && $validated['description'] !== '') {
            $settings['description'] = $validated['description'];
        }
        if (array_key_exists('rsvp_welcome_message', $validated) && $validated['rsvp_welcome_message'] !== null && trim((string) $validated['rsvp_welcome_message']) !== '') {
            $settings['rsvp_welcome_message'] = trim((string) $validated['rsvp_welcome_message']);
        }
        if (array_key_exists('program', $validated) && $validated['program'] !== null && trim((string) $validated['program']) !== '') {
            $settings['program'] = trim((string) $validated['program']);
        }
        if (array_key_exists('venue_address', $validated) && $validated['venue_address'] !== null && trim((string) $validated['venue_address']) !== '') {
            $settings['venue_address'] = trim((string) $validated['venue_address']);
        }
        if (array_key_exists('custom', $validated) && is_array($validated['custom'])) {
            $custom = array_values(array_filter(array_map(function ($row): array {
                return [
                    'label' => trim((string) ($row['label'] ?? '')),
                    'value' => trim((string) ($row['value'] ?? '')),
                ];
            }, $validated['custom']), fn (array $row): bool => $row['label'] !== '' || $row['value'] !== ''));
            if ($custom !== []) {
                $settings['custom'] = $custom;
            }
        }

        $event = Event::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'event_date' => isset($validated['event_date']) ? $validated['event_date'] : null,
            'venue_name' => $validated['venue_name'] ?? null,
            'settings' => $settings,
            'status' => EventStatus::Draft,
        ]);

        if ($request->filled('cropped_image')) {
            $event->addMediaFromBase64($request->input('cropped_image'), 'image/jpeg', 'image/png', 'image/webp')
                ->usingFileName('event-image.jpg')
                ->toMediaCollection('event-image');
        } elseif ($request->hasFile('image')) {
            $event->addMediaFromRequest('image')
                ->toMediaCollection('event-image');
        }

        return redirect()->route('dashboard.events.index')
            ->with('success', __('Event created.'));
    }

    public function show(Event $event): View
    {
        Gate::authorize('view', $event);

        $event->load(['guests', 'eventTables', 'invitations', 'eventBilling', 'organization', 'seatAssignments']);

        return view('dashboard.events.show', [
            'event' => $event,
            'eventLinks' => app(EventLinks::class),
        ]);
    }

    public function edit(Event $event): View
    {
        Gate::authorize('update', $event);

        return view('dashboard.events.edit', [
            'event' => $event,
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $validated = $request->validated();
        $settings = $event->settings ?? [];

        if ($request->boolean('remove_image')) {
            $event->clearMediaCollection('event-image');
        } elseif ($request->filled('cropped_image')) {
            $event->clearMediaCollection('event-image');
            $event->addMediaFromBase64($request->input('cropped_image'), 'image/jpeg', 'image/png', 'image/webp')
                ->usingFileName('event-image.jpg')
                ->toMediaCollection('event-image');
        } elseif ($request->hasFile('image')) {
            $event->addMediaFromRequest('image')
                ->toMediaCollection('event-image');
        }

        if (array_key_exists('description', $validated)) {
            $settings['description'] = $validated['description'] ?: null;
        }
        if (array_key_exists('rsvp_welcome_message', $validated)) {
            $settings['rsvp_welcome_message'] = trim((string) ($validated['rsvp_welcome_message'] ?? '')) !== '' ? trim((string) $validated['rsvp_welcome_message']) : null;
        }
        if (array_key_exists('program', $validated)) {
            $settings['program'] = trim((string) ($validated['program'] ?? '')) !== '' ? trim((string) $validated['program']) : null;
        }
        if (array_key_exists('venue_address', $validated)) {
            $settings['venue_address'] = trim((string) ($validated['venue_address'] ?? '')) !== '' ? trim((string) $validated['venue_address']) : null;
        }
        if (array_key_exists('custom', $validated)) {
            $custom = is_array($validated['custom']) ? $validated['custom'] : [];
            $settings['custom'] = array_values(array_filter(array_map(function ($row): array {
                return [
                    'label' => trim((string) ($row['label'] ?? '')),
                    'value' => trim((string) ($row['value'] ?? '')),
                ];
            }, $custom), fn (array $row): bool => $row['label'] !== '' || $row['value'] !== ''));
        }

        unset($validated['image'], $validated['cropped_image'], $validated['remove_image'], $validated['description'], $validated['rsvp_welcome_message'], $validated['program'], $validated['venue_address'], $validated['custom']);
        $event->update(array_merge($validated, ['settings' => $settings]));

        return redirect()->route('dashboard.events.show', $event)
            ->with('success', __('Event updated.'));
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return redirect()->route('dashboard.events.index')
            ->with('success', __('Event deleted.'));
    }
}
