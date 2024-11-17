<div x-data="{
        categories: {{ json_encode($entry->categories) }},
        updateCategories(newCategory) {
            console.log('Updating categories:', newCategory); // Debug log
            this.categories = [newCategory];
        }
    }"
    x-init="
        $watch('categories', value => console.log('Categories changed:', value)); // Debug log
        window.addEventListener('category-assignment-updated', (event) => {
            console.log('Event received:', event); // Debug log
            if (event.detail.entryIds.includes({{ $entry->id }})) {
                updateCategories(event.detail.category);
            }
        });
    "
    class="flex items-center space-x-1">

    <template x-if="categories.length > 0">
        <template x-for="category in categories" :key="category.id">
            <span class="px-2 py-0.5 text-xs rounded-full"
                  :class="{ 'border border-red-300': category.exclude_from_calculations }"
                  :style="{ backgroundColor: category.color }">
                <span x-text="category.name"></span>
                <template x-if="category.exclude_from_calculations">
                    <span class="ml-1 text-red-500">•</span>
                </template>
            </span>
        </template>
    </template>

    <template x-if="categories.length === 0">
        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">
            Uncategorized
        </span>
    </template>
</div>
