<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\Database\ReadWriteConnection;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * EventList - Livewire v4 View-Based Component
 *
 * Displays searchable, filterable list of events for an organization.
 * Uses read replica for queries and supports pagination.
 *
 * Location: resources/views/components/dashboard/⚡event-list/event-list.php
 * Route: dashboard.events.index
 */
new class extends Component
{
    use WithPagination;

    // Search and filter properties
    public string $search = '';
    public string $filterStatus = '';

    // Pagination
    public int $perPage = 15;

    /**
     * Reset all filters and search.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    /**
     * Clear search only.
     */
    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Render the component with filtered events.
     */
    public function render(View $view): View
    {
        // Get organization from session via OrganizationContext
        $organization = app(\App\Services\OrganizationContext::class)->current();
        if ($organization === null) {
            abort(404, 'No active organization.');
        }

        // Use read replica for dashboard queries
        $readConnection = app(ReadWriteConnection::class)->read()->getName();

        $events = Event::on($readConnection)
            ->where('organization_id', $organization->id)
            ->when($this->search !== '', function (Builder $query) {
                $searchTerm = '%' . $this->search . '%';
                $query->where('name', 'LIKE', $searchTerm)
                    ->orWhere('venue_name', 'LIKE', $searchTerm);
            })
            ->when($this->filterStatus !== '', function (Builder $query) {
                $query->where('status', EventStatus::from($this->filterStatus));
            })
            ->withCount('guests')
            ->orderByDesc('event_date')
            ->paginate($this->perPage);

        return $view->with([
            'organization' => $organization,
            'events' => $events,
            'hasActiveFilters' => $this->search !== '' || $this->filterStatus !== '',
        ]);
    }
}
