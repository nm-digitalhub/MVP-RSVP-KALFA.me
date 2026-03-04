<?php

declare(strict_types=1);

namespace App\Livewire\System\Accounts;

use App\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * System admin: list accounts with search/filter.
 */
final class Index extends Component
{
    use WithPagination;

    public string $search_id = '';

    public string $search_type = '';

    public string $search_owner_user_id = '';

    public string $search_sumit_customer_id = '';

    public string $search_name = '';

    #[Layout('layouts.app')]
    #[Title('System Accounts')]
    public function render(): View
    {
        $accounts = Account::query()
            ->with(['owner', 'organizations'])
            ->when($this->search_id !== '', fn (Builder $q) => $q->where('id', (int) $this->search_id))
            ->when($this->search_type !== '', fn (Builder $q) => $q->where('type', $this->search_type))
            ->when($this->search_owner_user_id !== '', fn (Builder $q) => $q->where('owner_user_id', (int) $this->search_owner_user_id))
            ->when($this->search_sumit_customer_id !== '', fn (Builder $q) => $q->where('sumit_customer_id', (int) $this->search_sumit_customer_id))
            ->when($this->search_name !== '', fn (Builder $q) => $q->where('name', 'like', '%'.$this->search_name.'%'))
            ->latest()
            ->paginate(15);

        return view('livewire.system.accounts.index', [
            'accounts' => $accounts,
        ]);
    }
}
