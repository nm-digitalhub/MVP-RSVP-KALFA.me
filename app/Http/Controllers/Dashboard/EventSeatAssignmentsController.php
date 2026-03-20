<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class EventSeatAssignmentsController extends Controller
{
    public function index(Event $event): View
    {
        Gate::authorize('view', $event);

        return view('dashboard.events.seat-assignments', ['event' => $event]);
    }
}
