<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Database\ReadWriteConnection;
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
        private OrganizationContext $context,
        private ReadWriteConnection $db,
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

        // Use read replica for dashboard queries
        $events = Event::on($this->db->read()->getName())
            ->where('organization_id', $organization->id)
            ->with(['organization.account', 'eventBilling'])
            ->withCount('guests')
            ->orderByDesc('event_date')
            ->get();

        $events->each->ensureAccessibleStatus();

        return view('dashboard.index', [
            'organization' => $organization,
            'events' => $events,
        ]);
    }
}
