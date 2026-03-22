@props([
    'headers' => [],
    'rows' => [],
    'emptyMessage' => __('No data available.'),
    'emptyIcon' => null,
])

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-stroke">
            <thead class="bg-surface">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-content-muted">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-card divide-y divide-stroke">
                @forelse($rows as $row)
                    <tr class="hover:bg-surface/50 transition-colors duration-150 cursor-pointer group">
                        @foreach($row as $cell)
                            <td class="px-4 py-3 text-sm text-content">
                                {{ $cell }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) }}" class="px-4 py-10 text-center text-sm text-content-muted">
                            @if($emptyIcon)
                                <div class="mx-auto mb-3 h-12 w-12 opacity-50">
                                    {{ $emptyIcon }}
                                </div>
                            @endif
                            <span class="block">{{ $emptyMessage }}</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
