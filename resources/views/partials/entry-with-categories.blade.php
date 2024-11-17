<div class="space-y-1">
    <div class="flex justify-between items-center text-sm pl-4">
        <div class="flex-1 grid grid-cols-3 gap-4">
            <span class="text-gray-600">
                {{ $entry->transaction_date->format('Y-m-d') }}
            </span>
            <span class="text-gray-800">{{ $entry->recipient }}</span>
            <span class="text-gray-600">{{ $entry->description }}</span>
        </div>
        <span class="text-gray-700 ml-4 {{ $entry->getStatusClass() }}">
            {{ number_format($entry->amount, 2) }}
        </span>
    </div>

    <div class="pl-4 flex items-center space-x-1">
        @if($entry->categories->isNotEmpty())
            @foreach($entry->categories as $category)
                <span class="px-2 py-0.5 text-xs rounded-full {{ $category->exclude_from_calculations ? 'border border-red-300' : '' }}"
                      style="background-color: {{ $category->color }}">
                    {{ $category->name }}
                    @if($category->exclude_from_calculations)
                        <span class="ml-1 text-red-500">•</span>
                    @endif
                </span>
            @endforeach
        @else
            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">
                Uncategorized
            </span>
        @endif
    </div>
</div>
