<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\View\View;

/**
 * Public event page by slug. Only active events.
 */
class PublicEventController extends Controller
{
    public function show(string $slug): View
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if ($event->ensureAccessibleStatus()) {
            $event->refresh();
        }

        if ($event->status !== EventStatus::Active) {
            abort(404, 'Event not available.');
        }

        return view('events.show', [
            'event' => $event,
        ]);
    }
}
