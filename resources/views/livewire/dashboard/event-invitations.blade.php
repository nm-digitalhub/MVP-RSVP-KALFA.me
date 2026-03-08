<div>
    <div class="mb-4">
        <a href="{{ route('dashboard.events.show', $event) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('Back to event') }}</a>
    </div>

    @can('update', $event)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <h3 class="text-sm font-medium text-gray-900 mb-2">{{ __('Create invitation') }}</h3>
            <form wire:submit="createInvitation" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[200px]">
                    <x-input-label for="create_guest" :value="__('Link to guest (optional)')" />
                    <select id="create_guest" wire:model="createForGuestId" class="mt-1 block w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                        <option value="">{{ __('— No guest —') }}</option>
                        @foreach($guestsWithoutInvitation as $g)
                            <option wire:key="guest-{{ $g->id }}" value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button type="submit">{{ __('Create') }}</x-primary-button>
            </form>
        </div>
    @endcan

    @if($event->status !== \App\Enums\EventStatus::Active)
        <p class="mb-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3" role="status">
            {{ __('The RSVP link will be available once the event is active (after payment is completed).') }}
        </p>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('Guest') }}</th>
                    <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                    <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('RSVP link') }}</th>
                    <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($invitations as $inv)
                    <tr wire:key="invitation-{{ $inv->id }}">
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $inv->guest?->name ?? __('—') }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $inv->status?->value ? __($inv->status->value) : __('—') }}</td>
                        <td class="px-4 py-2 text-sm">
                            <a href="{{ url('rsvp/' . $inv->slug) }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900 break-all">{{ url('rsvp/' . $inv->slug) }}</a>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            @can('update', $event)
                                @if($inv->status === \App\Enums\InvitationStatus::Pending)
                                    <button type="button" wire:click="markSent({{ $inv->id }})" class="min-h-[44px] inline-flex items-center px-2 text-indigo-600 hover:text-indigo-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 rounded cursor-pointer transition-colors duration-200">{{ __('Mark as sent') }}</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No invitations yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
