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
                            <div class="mb-8">
                                <h3 class="font-bold text-lg mb-2">{{ $csv_file->original_filename }}</h3>

                                @if(isset($csv_file->entries[0]))
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full bg-white border">
                                            @php
                                                // Check which columns have any data
                                                $hasDate = $csv_file->entries->contains(function($entry) {
                                                    return !empty($entry->transaction_date);
                                                });
                                                $hasDescription = $csv_file->entries->contains(function($entry) {
                                                    return !empty($entry->description);
                                                });
                                                $hasRecipient = $csv_file->entries->contains(function($entry) {
                                                    return !empty($entry->recipient);
                                                });
                                                $hasAmount = $csv_file->entries->contains(function($entry) {
                                                    return !empty($entry->amount);
                                                });
                                            @endphp

                                            <thead>
                                                <tr>
                                                    <th class="border px-4 py-2 bg-gray-100 w-10"></th>
                                                    @if($hasDate)
                                                        <th class="border px-4 py-2 bg-gray-100">Date</th>
                                                    @endif
                                                    @if($hasDescription)
                                                        <th class="border px-4 py-2 bg-gray-100">Description</th>
                                                    @endif
                                                    @if($hasRecipient)
                                                        <th class="border px-4 py-2 bg-gray-100">Recipient</th>
                                                    @endif
                                                    @if($hasAmount)
                                                        <th class="border px-4 py-2 bg-gray-100">Amount</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($csv_file->entries[0]->getGroupedEntries() as $group)
                                                    @if(count($group['similar_entries']) > 0)
                                                        <!-- Grouped Row -->
                                                        <tr class="group-row hover:bg-gray-50 bg-gray-100">
                                                            <td class="border px-4 py-2">
                                                                <button onclick="toggleGroup('group-{{ $group['main_entry']->id }}')"
                                                                        class="text-blue-500 w-6 h-6 flex items-center justify-center border rounded">
                                                                    <span class="expand-icon">+</span>
                                                                    <span class="collapse-icon hidden">−</span>
                                                                </button>
                                                            </td>
                                                            @if($hasDate)
                                                                <td class="border px-4 py-2">-</td>
                                                            @endif
                                                            @if($hasDescription)
                                                                <td class="border px-4 py-2 font-medium">
                                                                    Grouped transaction: {{ Str::limit($group['main_entry']->description, 30) }}
                                                                    <span class="text-gray-500 text-sm ml-2">({{ count($group['similar_entries']) + 1 }} entries)</span>
                                                                </td>
                                                            @endif
                                                            @if($hasRecipient)
                                                                <td class="border px-4 py-2">{{ $group['main_entry']->recipient }}</td>
                                                            @endif
                                                            @if($hasAmount)
                                                                <td class="border px-4 py-2 font-bold">{{ number_format($group['total_amount'], 2) }}</td>
                                                            @endif
                                                        </tr>
                                                        <!-- Individual Entries (Hidden by Default) -->
                                                        <tr class="group-{{ $group['main_entry']->id }} hidden">
                                                            <td colspan="100%" class="p-0">
                                                                <div class="bg-gray-50">
                                                                    <table class="w-full">
                                                                        <!-- Main Entry -->
                                                                        <tr>
                                                                            <td class="border-l px-4 py-2 w-10"></td>
                                                                            @if($hasDate)
                                                                                <td class="border-l px-4 py-2">{{ $group['main_entry']->transaction_date->format('Y-m-d') }}</td>
                                                                            @endif
                                                                            @if($hasDescription)
                                                                                <td class="border-l px-4 py-2">{{ $group['main_entry']->description }}</td>
                                                                            @endif
                                                                            @if($hasRecipient)
                                                                                <td class="border-l px-4 py-2">{{ $group['main_entry']->recipient }}</td>
                                                                            @endif
                                                                            @if($hasAmount)
                                                                                <td class="border-l border-r px-4 py-2">{{ number_format($group['main_entry']->amount, 2) }}</td>
                                                                            @endif
                                                                        </tr>
                                                                        <!-- Similar Entries -->
                                                                        @foreach($group['similar_entries'] as $entry)
                                                                            <tr>
                                                                                <td class="border-l px-4 py-2 w-10"></td>
                                                                                @if($hasDate)
                                                                                    <td class="border-l px-4 py-2">{{ $entry->transaction_date->format('Y-m-d') }}</td>
                                                                                @endif
                                                                                @if($hasDescription)
                                                                                    <td class="border-l px-4 py-2">{{ $entry->description }}</td>
                                                                                @endif
                                                                                @if($hasRecipient)
                                                                                    <td class="border-l px-4 py-2">{{ $entry->recipient }}</td>
                                                                                @endif
                                                                                @if($hasAmount)
                                                                                    <td class="border-l border-r px-4 py-2">{{ number_format($entry->amount, 2) }}</td>
                                                                                @endif
                                                                            </tr>
                                                                        @endforeach
                                                                    </table>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @else
                                                        <!-- Single Entry -->
                                                        <tr>
                                                            <td class="border px-4 py-2"></td>
                                                            @if($hasDate)
                                                                <td class="border px-4 py-2">{{ $group['main_entry']->transaction_date->format('Y-m-d') }}</td>
                                                            @endif
                                                            @if($hasDescription)
                                                                <td class="border px-4 py-2">{{ $group['main_entry']->description }}</td>
                                                            @endif
                                                            @if($hasRecipient)
                                                                <td class="border px-4 py-2">{{ $group['main_entry']->recipient }}</td>
                                                            @endif
                                                            @if($hasAmount)
                                                                <td class="border px-4 py-2">{{ number_format($group['main_entry']->amount, 2) }}</td>
                                                            @endif
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-gray-500">
                                        @if(!$csv_file->is_mapped)
                                            <a href="{{ route('csv.map', $csv_file->id) }}" class="text-blue-500 hover:underline">
                                                Map columns for this file
                                            </a>
                                        @else
                                            No entries processed yet
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p>No CSV files uploaded yet.</p>
                    @endif
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
