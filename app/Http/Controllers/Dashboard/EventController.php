<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

/**
 * Event management (dashboard). Policy-protected.
 */
class EventController extends Controller
{
    public function show(Event $event): View
    {
        $this->authorize('view', $event);

        $event->load(['guests', 'eventTables', 'invitations', 'eventBilling', 'organization']);

        return view('dashboard.events.show', [
            'event' => $event,
        ]);
    }
}
