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

                    <!-- Data Display -->
                    @if(isset($csv_files) && $csv_files->count() > 0)
                        @foreach($csv_files as $csv_file)
                            <div class="mb-8">
                                <h3 class="font-bold text-lg mb-2">{{ $csv_file->filename }}</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full bg-white border">
                                        <thead>
                                            <tr>
                                                @foreach(array_keys($csv_file->data[0]) as $header)
                                                    <th class="border px-4 py-2 bg-gray-100">{{ $header }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($csv_file->data as $row)
                                                <tr>
                                                    @foreach($row as $value)
                                                        <td class="border px-4 py-2">{{ $value }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
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
