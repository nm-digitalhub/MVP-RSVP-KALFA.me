<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\EventStatus;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class EventList extends Component
{
    use WithPagination;

    public Organization $organization;

    public string $search = '';

    public string $filterStatus = '';

    #[Layout('layouts.app')]
    #[Title('Events')]
    public function mount(): void
    {
        $this->organization = auth()->user()->currentOrganization;
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus']);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Get the events query with search and filters applied.
     */
    protected function getEventsQuery()
    {
        $query = $this->organization->events()
            ->withCount('guests');

        // Apply search filter
        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('venue_name', 'like', $term);
            });
        }

        // Apply status filter
        if ($this->filterStatus !== '') {
            $query->where('status', EventStatus::from($this->filterStatus));
        }

        return $query->orderBy('event_date', 'desc');
    }

    public function render(): View
    {
        $eventsQuery = $this->getEventsQuery();
        $events = $eventsQuery->paginate(10);

        return view('livewire.dashboard.event-list', [
            'organization' => $this->organization,
            'events' => $events,
            'hasActiveFilters' => $this->search !== '' || $this->filterStatus !== '',
        ]);
    }
}
