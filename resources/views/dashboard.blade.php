<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Upload Form -->
                    <form action="{{ route('csv.upload') }}" method="POST" enctype="multipart/form-data" class="mb-8">
                        @csrf
                        <div class="flex items-center gap-4">
                            <input type="file" name="csv_file" accept=".csv" class="border p-2">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                                Upload CSV
                            </button>
                        </div>
                    </form>

                    <!-- Files List -->
                    @if(isset($csv_files) && $csv_files->count() > 0)
                        @foreach($csv_files as $csv_file)
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                                <div class="p-6">
                                    <h2 class="text-lg font-semibold mb-4">{{ $csv_file->original_filename }}</h2>

                                    @foreach($csv_file->entries->first()?->getGroupedEntries() ?? [] as $typeGroup)
                                        <!-- Type Group Header -->
                                        <div class="bg-gray-100 p-4 mb-4 rounded-lg">
                                            <h3 class="font-medium text-gray-700 mb-2">
                                                Type: {{ ucfirst($typeGroup['type']) }}
                                                <span class="text-sm text-gray-500">
                                                    (Total: {{ number_format($typeGroup['total_amount'], 2) }})
                                                </span>
                                            </h3>

                                            <!-- Similarity Groups within Type -->
                                            @foreach($typeGroup['groups'] as $index => $group)
                                                <div class="bg-{{ $index % 2 == 0 ? 'gray-50' : 'white' }} p-4 mb-2 rounded">
                                                    <!-- Group Header -->
                                                    <div class="border-b border-gray-200 pb-2 mb-3">
                                                        <div class="flex justify-between items-center">
                                                            <div class="space-y-2">
                                                                <div class="font-medium text-gray-700">
                                                                    <span>{{ $group['main_entry']->recipient }}</span>
                                                                    <span class="mx-2">•</span>
                                                                    <span>{{ $group['main_entry']->description }}</span>
                                                                </div>
                                                                <div class="flex items-center space-x-2">
                                                                    @include('partials.category-badge', ['entry' => $group['main_entry']])
                                                                    <x-category-selector
                                                                        :entryIds="collect($group['similar_entries'])->pluck('id')->push($group['main_entry']->id)"
                                                                        :currentCategories="$group['main_entry']->categories"
                                                                    />
                                                                </div>
                                                            </div>
                                                            <div x-data="{
                                                                groupTotal: {{ $group['total_amount'] }},
                                                                excludedTotal: {{ $group['main_entry']->shouldExclude() ? $group['main_entry']->amount : 0 }}
                                                            }"
                                                                x-on:category-updated.window="
                                                                    if ($event.detail.category.exclude_from_calculations) {
                                                                        groupTotal -= entry.amount;
                                                                        excludedTotal += entry.amount;
                                                                    }
                                                                "
                                                                class="font-medium">
                                                                Total: <span x-text="new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(groupTotal)"></span>
                                                                <template x-if="excludedTotal > 0">
                                                                    <span class="text-sm text-gray-500">
                                                                        (Excluded: <span x-text="new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(excludedTotal)"></span>)
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Individual Entries -->
                                                    <div class="space-y-2">
                                                        <!-- Main Entry -->
                                                        <div class="flex justify-between items-center text-sm pl-4">
                                                            <div class="flex-1 grid grid-cols-3 gap-4">
                                                                <span class="text-gray-600">
                                                                    {{ $group['main_entry']->transaction_date->format('Y-m-d') }}
                                                                </span>
                                                                <span class="text-gray-800">{{ $group['main_entry']->recipient }}</span>
                                                                <span class="text-gray-600">{{ $group['main_entry']->description }}</span>
                                                            </div>
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-gray-700 {{ $group['main_entry']->getStatusClass() }}">
                                                                    {{ number_format($group['main_entry']->amount, 2) }}
                                                                </span>
                                                                <x-category-selector
                                                                    :entryIds="[$group['main_entry']->id]"
                                                                    :currentCategories="$group['main_entry']->categories"
                                                                />
                                                            </div>
                                                        </div>

                                                        <!-- Similar Entries -->
                                                        @foreach($group['similar_entries'] as $similar)
                                                            <div class="flex justify-between items-center text-sm pl-4">
                                                                <div class="flex-1 grid grid-cols-3 gap-4">
                                                                    <span class="text-gray-600">
                                                                        {{ $similar->transaction_date->format('Y-m-d') }}
                                                                    </span>
                                                                    <span class="text-gray-800">{{ $similar->recipient }}</span>
                                                                    <span class="text-gray-600">{{ $similar->description }}</span>
                                                                </div>
                                                                <div class="flex items-center space-x-2">
                                                                    <span class="text-gray-700 {{ $similar->getStatusClass() }}">
                                                                        {{ number_format($similar->amount, 2) }}
                                                                    </span>
                                                                    <x-category-selector
                                                                        :entryIds="[$similar->id]"
                                                                        :currentCategories="$similar->categories"
                                                                    />
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p>No CSV files uploaded yet.</p>
                    @endif

                    @foreach($csv_files as $file)
                        <tr>
                            <td>{{ $file->id }}</td>
                            <td>{{ $file->original_filename }}</td>
                            <td>
                                <a href="{{ route('csv.show', $file->id) }}"
                                    class="text-blue-600 hover:text-blue-800">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
function toggleGroup(groupClass) {
    const rows = document.getElementsByClassName(groupClass);
    const button = event.currentTarget;
    const expandIcon = button.querySelector('.expand-icon');
    const collapseIcon = button.querySelector('.collapse-icon');

    Array.from(rows).forEach(row => {
        row.classList.toggle('hidden');
    });

    expandIcon.classList.toggle('hidden');
    collapseIcon.classList.toggle('hidden');
}
</script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('categorySelector', ({ entryIds, isGroup, currentCategories = [] }) => ({
        isOpen: false,
        categories: [],
        entryIds: entryIds,
        isGroup: isGroup,
        currentCategories: currentCategories,
        loading: false,

        async init() {
            // Load categories when component initializes
            await this.loadCategories();
        },

        async loadCategories() {
            try {
                const response = await fetch('/api/categories');
                this.categories = await response.json();
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },

        toggle() {
            this.isOpen = !this.isOpen;
        },

        close() {
            this.isOpen = false;
        },

        async assignCategory(categoryId) {
            if (this.isGroup && !confirm('Assign this category to all entries in the group?')) {
                return;
            }

            this.loading = true;
            try {
                const response = await fetch('/assign-category', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        entry_ids: this.entryIds,
                        category_id: categoryId
                    })
                });

                if (!response.ok) throw new Error('Failed to assign category');

                // Optimistic UI update
                this.currentCategories = [this.categories.find(c => c.id === categoryId)];
                this.close();

                // Trigger a success message
                Alpine.store('flash').success('Category assigned successfully');

            } catch (error) {
                console.error('Failed to assign category:', error);
                Alpine.store('flash').error('Failed to assign category');
            } finally {
                this.loading = false;
            }
        },

        isCategoryAssigned(categoryId) {
            return this.currentCategories.some(c => c.id === categoryId);
        }
    }));

    // Global flash message store
    Alpine.store('flash', {
        message: null,
        type: null,
        show: false,

        success(message) {
            this.message = message;
            this.type = 'success';
            this.show = true;
            setTimeout(() => this.show = false, 3000);
        },

        error(message) {
            this.message = message;
            this.type = 'error';
            this.show = true;
            setTimeout(() => this.show = false, 3000);
        }
    });
});
</script>
