<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

/**
 * Event guests management page. Renders view that mounts Livewire EventGuests.
 */
class EventGuestsController extends Controller
{
    public function index(Event $event): View
    {
        $this->authorize('view', $event);

        return view('dashboard.events.guests', [
            'event' => $event,
        ]);
    }
}
