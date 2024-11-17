<div x-data="categorySelector({
    entryIds: {{ json_encode($entryIds) }},
    isGroup: {{ count($entryIds) > 1 ? 'true' : 'false' }},
    currentCategories: {{ json_encode($currentCategories ?? []) }}
})" class="relative">
    <!-- Category Button -->
    <button @click="toggle"
            class="inline-flex items-center px-2 py-1 text-xs rounded-md"
            :class="isOpen ? 'bg-gray-200' : 'bg-gray-100 hover:bg-gray-200'"
            :disabled="loading">
        <span x-text="loading ? 'Assigning...' : 'Categories'"></span>
        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="isOpen"
         @click.away="close"
         x-transition
         class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
        <div class="py-1">
            <template x-for="category in categories" :key="category.id">
                <button @click="assignCategory(category.id)"
                        class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
                        :class="{ 'bg-gray-50 font-medium': isCategoryAssigned(category.id) }">
                    <span class="inline-block w-3 h-3 rounded-full mr-2"
                          :style="{ backgroundColor: category.color }"></span>
                    <span x-text="category.name"></span>
                </button>
            </template>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('categorySelector', ({ entryIds, isGroup, currentCategories = [] }) => ({
                // ... other properties ...

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

                        const data = await response.json();
                        console.log('Category assigned:', data); // Debug log

                        // Dispatch event with more specific name
                        const event = new CustomEvent('category-assignment-updated', {
                            detail: {
                                entryIds: this.entryIds,
                                category: data.category
                            },
                            bubbles: true // Make sure event bubbles up
                        });
                        window.dispatchEvent(event);
                        console.log('Event dispatched:', event); // Debug log

                        this.currentCategories = [data.category];
                        this.close();

                        Alpine.store('flash').success('Category assigned successfully');

                    } catch (error) {
                        console.error('Failed to assign category:', error);
                        Alpine.store('flash').error('Failed to assign category');
                    } finally {
                        this.loading = false;
                    }
                }
            }));
        });
    </script>
</div>

<!-- Flash Messages -->
<div x-data
     x-show="$store.flash.show"
     x-transition
     class="fixed bottom-4 right-4 px-4 py-2 rounded-lg"
     :class="{
         'bg-green-100 text-green-800': $store.flash.type === 'success',
         'bg-red-100 text-red-800': $store.flash.type === 'error'
     }">
    <span x-text="$store.flash.message"></span>
</div>
