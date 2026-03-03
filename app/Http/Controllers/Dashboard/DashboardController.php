<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * STEP 1 — Dashboard Skeleton (read-only).
 * Resolves active organization via OrganizationContext; loads events for current org only.
 */
class DashboardController extends Controller
{
    public function __construct(
        private OrganizationContext $context
    ) {}

    /**
     * Dashboard index: active organization + events table (read-only).
     */
    public function index(Request $request): View
    {
        $organization = $this->context->current();
        if ($organization === null) {
            abort(404, 'No active organization.');
        }

        $events = $organization->events()
            ->withCount('guests')
            ->orderByDesc('event_date')
            ->get();

        return view('dashboard.index', [
            'organization' => $organization,
            'events' => $events,
        ]);
    }
}
