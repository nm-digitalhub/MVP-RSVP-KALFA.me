<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class EventTablesController extends Controller
{
    public function index(Event $event): View
    {
        $this->authorize('view', $event);
        $event->load('eventTables');

        return view('dashboard.events.tables', ['event' => $event]);
    }
}
