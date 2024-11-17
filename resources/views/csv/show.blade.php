<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-lg font-semibold mb-4">
                        CSV File: {{ $csv_file->original_filename }}
                    </h2>

                    <div class="mt-4">
                        <p>Total Entries: {{ $csv_file->total_entries }}</p>
                        <p>Uploaded: {{ $csv_file->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
