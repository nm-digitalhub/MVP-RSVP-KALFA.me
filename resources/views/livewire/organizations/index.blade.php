<div class="space-y-6">
    @if(session('error'))
        <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- CTA section --}}
    <div class="flex justify-start">
        <a href="{{ route('organizations.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('Create New Organization') }}
        </a>
    </div>

    {{-- Centered card list — focused management layout --}}
    <div class="bg-white/80 rounded-xl shadow-sm border border-gray-200/80 overflow-hidden">
        <div class="p-6">
            <ul class="space-y-3">
                @forelse($organizations as $org)
                    <li>
                        <form action="{{ route('organizations.switch', $org) }}" method="POST" class="block">
                            @csrf
                            <button type="submit" class="w-full text-left rounded-lg border-2 border-gray-200 bg-gray-50/50 hover:border-indigo-300 hover:bg-indigo-50/50 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition p-4 flex items-center justify-between gap-4">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $org->name }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $org->events_count ?? 0 }} {{ __('events') }}</span>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-gray-500 rounded-lg border-2 border-dashed border-gray-200">
                        {{ __('No organizations yet.') }}
                        <a href="{{ route('organizations.create') }}" class="ml-1 text-indigo-600 hover:underline font-medium">{{ __('Create one') }}</a>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
