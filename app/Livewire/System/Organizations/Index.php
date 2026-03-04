<?php

declare(strict_types=1);

namespace App\Livewire\System\Organizations;

use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $filter_suspended = '';

    public string $filter_no_events = '';

    public string $filter_no_users = '';

    public string $search_name = '';

    public string $search_owner_email = '';

    #[Layout('layouts.app')]
    #[Title('System Organizations')]
    public function render(): View
    {
        $organizations = Organization::withCount(['users', 'events'])
            ->when($this->filter_suspended === '1', fn (Builder $q) => $q->where('is_suspended', true))
            ->when($this->filter_suspended === '0', fn (Builder $q) => $q->where('is_suspended', false))
            ->when($this->filter_no_events === '1', fn (Builder $q) => $q->having('events_count', '=', 0))
            ->when($this->filter_no_users === '1', fn (Builder $q) => $q->having('users_count', '=', 0))
            ->when($this->search_name !== '', fn (Builder $q) => $q->where('name', 'like', '%'.$this->search_name.'%'))
            ->when($this->search_owner_email !== '', function (Builder $q) {
                $q->whereHas('users', function (Builder $u) {
                    $u->where('users.email', 'like', '%'.$this->search_owner_email.'%')
                        ->where('organization_users.role', 'owner');
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.system.organizations.index', [
            'organizations' => $organizations,
        ]);
    }
}
