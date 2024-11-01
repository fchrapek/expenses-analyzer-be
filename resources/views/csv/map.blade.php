<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-lg font-semibold mb-4">Map CSV Columns for: {{ $csv_file->original_filename }}</h2>

                    <form action="{{ route('csv.save-mapping', $csv_file->id) }}" method="POST">
                        @csrf

                        @foreach($headers as $header)
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ $header }}
                                </label>
                                <select name="mappings[{{ $header }}]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">-- Skip this column --</option>
                                    @foreach($mapping_options as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach

                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                            Save Mapping
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
