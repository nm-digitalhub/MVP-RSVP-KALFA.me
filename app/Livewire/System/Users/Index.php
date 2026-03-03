<?php

namespace App\Livewire\System\Users;

use App\Models\User;
use App\Services\SystemAuditLogger;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $filter_system_admin = '';

    public string $filter_no_organization = '';

    public string $filter_recent = '';

    public string $filter_suspended = '';

    public string $search = '';

    #[Layout('layouts.app')]
    #[Title('System Users')]
    public function toggleAdmin(int $userId): void
    {
        $target = User::find($userId);
        if (! $target) {
            return;
        }
        if ($target->id === auth()->id()) {
            return;
        }
        $wasAdmin = $target->is_system_admin;
        $target->update(['is_system_admin' => ! $wasAdmin]);

        SystemAuditLogger::log(
            actor: auth()->user(),
            action: $wasAdmin ? 'system_admin.demoted' : 'system_admin.promoted',
            target: $target,
            metadata: ['user_id' => $target->id, 'email' => $target->email],
        );
    }

    public function render()
    {
        $users = User::withCount('organizations')
            ->when($this->filter_system_admin === '1', fn ($q) => $q->where('is_system_admin', true))
            ->when($this->filter_system_admin === '0', fn ($q) => $q->where('is_system_admin', false))
            ->when($this->filter_no_organization === '1', fn ($q) => $q->whereDoesntHave('organizations'))
            ->when($this->filter_recent === '7', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->when($this->filter_recent === '30', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->when($this->filter_suspended === '1', fn ($q) => $q->where('is_disabled', true))
            ->when($this->filter_suspended === '0', fn ($q) => $q->where('is_disabled', false))
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            }))
            ->latest()
            ->paginate(20);

        return view('livewire.system.users.index', [
            'users' => $users,
        ]);
    }
}
