<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Event guests management: list, add, edit, delete, import.
 */
final class EventGuests extends Component
{
    use WithFileUploads;

    public Event $event;

    #[On('echo-private:event.{event.id},RsvpReceived')]
    public function onRsvpReceived(): void
    {
        // Refresh the list
    }

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $group_name = '';

    public string $notes = '';

    public $importFile = null;

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function openCreate(): void
    {
        $this->authorize('update', $this->event);
        $this->editingId = null;
        $this->resetGuestForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $guest = $this->event->guests()->find($id);
        if ($guest === null) {
            return;
        }
        $this->authorize('update', $guest);
        $this->editingId = $guest->id;
        $this->name = $guest->name;
        $this->email = $guest->email ?? '';
        $this->phone = $guest->phone ?? '';
        $this->group_name = $guest->group_name ?? '';
        $this->notes = $guest->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->event);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'group_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'group_name' => $this->group_name ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editingId !== null) {
            $guest = $this->event->guests()->find($this->editingId);
            if ($guest) {
                $this->authorize('update', $guest);
                $guest->update($data);
            }
        } else {
            $maxOrder = $this->event->guests()->max('sort_order') ?? -1;
            $this->event->guests()->create(array_merge($data, ['sort_order' => $maxOrder + 1]));
        }

        $this->showForm = false;
        $this->editingId = null;
        $this->resetGuestForm();
    }

    public function deleteGuest(int $id): void
    {
        $guest = $this->event->guests()->find($id);
        if ($guest === null) {
            return;
        }
        $this->authorize('delete', $guest);
        $guest->delete();
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetGuestForm();
    }

    public function import(): void
    {
        $this->authorize('update', $this->event);
        $this->validate(['importFile' => 'required|file|mimes:csv,txt']);

        $path = $this->importFile->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->addError('importFile', __('Could not open file.'));

            return;
        }

        $headers = fgetcsv($handle, 0);
        if ($headers === false) {
            fclose($handle);
            $this->addError('importFile', __('Invalid CSV format.'));

            return;
        }
        $headerMap = array_flip($headers);
        $maxOrder = $this->event->guests()->max('sort_order') ?? -1;
        $imported = 0;

        while (($data = fgetcsv($handle, 0)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }
            $row = array_combine($headerMap, $data);
            $name = trim($row['name'] ?? $row['שם'] ?? '');
            $email = trim($row['email'] ?? '');
            $phone = trim($row['phone'] ?? '');
            $notes = trim($row['notes'] ?? '');
            if ($name === '' && $email === '') {
                continue;
            }
            $this->event->guests()->create([
                'name' => $name ?: '—',
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'notes' => $notes ?: null,
                'sort_order' => ++$maxOrder,
            ]);
            $imported++;
        }
        fclose($handle);
        $this->importFile = null;
        $this->dispatch('imported', count: $imported);
    }

    private function resetGuestForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->group_name = '';
        $this->notes = '';
    }

    public function render(): View
    {
        $this->authorize('view', $this->event);

        $guests = $this->event->guests()->orderBy('sort_order')->get();

        return view('livewire.dashboard.event-guests', [
            'guests' => $guests,
        ]);
    }
}
