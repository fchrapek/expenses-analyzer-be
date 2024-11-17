<?php

namespace App\Http\Controllers;

use App\Models\CsvFile;
use App\Models\CsvEntry;
use App\Models\CsvColumnMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Controller for handling CSV file operations
 * Manages upload, viewing, mapping, and processing of CSV files
 */
class CsvController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Display Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Display a list of all uploaded CSV files
     * Route: GET /dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $csv_files = CsvFile::with('entries')
            ->latest()
            ->get();

        return view('dashboard', compact('csv_files'));
    }

    /**
     * Display a specific CSV file and its entries
     * Route: GET /csv/{id}/view
     *
     * @param  int  $id  The ID of the CSV file
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $csv_file = CsvFile::with(['entries.categories'])->findOrFail($id);
        $entries = $csv_file->entries;
        $categories = \App\Models\Category::all();

        // Debug categories
        Log::info('Categories loaded:', ['count' => $categories->count(), 'categories' => $categories->toArray()]);

        // Group entries by type if type exists
        $groupedEntries = $entries->groupBy('type');
        
        return Inertia::render('Csv/View', [
            'entries' => $groupedEntries->count() ? $groupedEntries : $entries,
            'groupedByType' => $groupedEntries->count() > 0,
            'csvFileId' => $id,
            'categories' => $categories
        ]);
    }

    /**
     * Show the column mapping interface
     * Route: GET /csv/{id}/map
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function showMapping($id)
    {
        $csv_file = CsvFile::with('columnMappings')->findOrFail($id);
        
        // Get existing mappings
        $existingMappings = $csv_file->columnMappings()
            ->where('mapping_type', 'column')
            ->pluck('maps_to', 'column_name')
            ->toArray();

        return Inertia::render('Csv/Map', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'csvFile' => [
                'id' => $csv_file->id,
                'headers' => $csv_file->headers,
                'existingMappings' => $existingMappings
            ],
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

    /**
     * Save the column mappings and process the CSV
     * Route: POST /csv/{id}/map
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveMapping(Request $request, $id)
    {
        $csv_file = CsvFile::findOrFail($id);
        
        // Validate required fields
        $mappings = $request->input('mappings');
        $requiredFields = ['date', 'amount', 'description'];
        $selectedFields = array_values($mappings);
        
        $missingFields = array_diff($requiredFields, $selectedFields);
        if (!empty($missingFields)) {
            return back()->withErrors([
                'mappings' => 'The following fields are required: ' . implode(', ', $missingFields)
            ]);
        }

        try {
            DB::beginTransaction();

            // Store existing entries with their categories
            $existingEntries = $csv_file->entries()
                ->with('categories')
                ->get()
                ->mapWithKeys(function ($entry) {
                    // Create a unique key based on description and amount
                    $key = md5($entry->description . '|' . $entry->amount);
                    return [$key => $entry->categories->pluck('id')->toArray()];
                });

            // Delete existing column mappings and entries
            $csv_file->columnMappings()->where('mapping_type', 'column')->delete();
            $csv_file->entries()->delete();

            // Save the new mappings
            foreach ($mappings as $header => $field) {
                if (empty($field)) continue;
                
                CsvColumnMapping::create([
                    'csv_file_id' => $csv_file->id,
                    'column_name' => $header,
                    'maps_to' => $field,
                    'mapping_type' => 'column'
                ]);
            }

            // Read and process the CSV file
            if (!Storage::disk('private')->exists($csv_file->filename)) {
                throw new \Exception('CSV file not found: ' . $csv_file->filename);
            }
            
            $file_contents = Storage::disk('private')->get($csv_file->filename);
            $csv_data = array_map('str_getcsv', explode("\n", trim($file_contents)));
            $headers = array_shift($csv_data);

            // Create entries
            foreach ($csv_data as $row) {
                if (empty($row) || count($row) !== count($headers)) {
                    continue;
                }

                $row_data = array_combine($headers, $row);
                $entry_data = [];

                $columnMappings = $csv_file->columnMappings()
                    ->where('mapping_type', 'column')
                    ->pluck('maps_to', 'column_name')
                    ->toArray();

                foreach ($columnMappings as $header => $field) {
                    $value = $this->extractMappedValue($row_data, $columnMappings, $field);
                    switch ($field) {
                        case 'date':
                            $entry_data['transaction_date'] = date('Y-m-d', strtotime($value));
                            break;
                        case 'amount':
                            $entry_data['amount'] = floatval(preg_replace('/[^0-9.-]/', '', $value));
                            break;
                        case 'type':
                            $entry_data['type'] = $value;
                            break;
                        case 'description':
                            $entry_data['description'] = $value;
                            break;
                        case 'recipient':
                            $entry_data['recipient'] = $value;
                            break;
                        case 'currency':
                            $entry_data['currency'] = $value;
                            break;
                    }
                }

                if (!isset($entry_data['currency'])) {
                    $entry_data['currency'] = 'PLN';
                }

                $entry_data['csv_file_id'] = $csv_file->id;
                $entry_data['original_data'] = json_encode($row_data);
                
                // Create the entry
                $entry = CsvEntry::create($entry_data);

                // Check if this entry had categories before and restore them
                $entryKey = md5($entry_data['description'] . '|' . $entry_data['amount']);
                if (isset($existingEntries[$entryKey])) {
                    $entry->categories()->attach($existingEntries[$entryKey]);
                } else {
                    // If no existing categories, try to find similar entries' categories
                    $categoryIds = $this->findSimilarEntryCategories($entry_data['description']);
                    if (!empty($categoryIds)) {
                        $entry->categories()->attach($categoryIds);
                    }
                }
            }

            $csv_file->update(['is_mapped' => true]);
            DB::commit();

            return redirect()->route('csv.view', $csv_file->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving CSV mapping: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to save mapping: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle the upload of a new CSV file
     * Route: POST /csv/upload
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('csv_file');
            $filename = $file->store('csv_files', 'private');

            // Create CSV File record
            $csv_file = CsvFile::create([
                'filename'          => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'is_mapped'         => false,
            ]);

            // Extract and store headers
            if (!Storage::disk('private')->exists($csv_file->filename)) {
                throw new \Exception('CSV file not found: ' . $csv_file->filename);
            }
            
            $file_contents = Storage::disk('private')->get($csv_file->filename);
            $csv_data = array_map('str_getcsv', explode("\n", $file_contents));
            $headers  = array_shift($csv_data);
            $csv_file->update(['headers' => $headers]);

            DB::commit();

            return redirect()
                ->route('csv.map', $csv_file->id)
                ->with('success', 'Please map the CSV columns');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Error uploading CSV: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Find similar entries and their categories
     *
     * @param string $description
     * @return array
     */
    private function findSimilarEntryCategories($description)
    {
        return CsvEntry::where('description', 'LIKE', '%' . $description . '%')
            ->whereHas('categories')
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->unique('id')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Process CSV file using the saved column mappings
     *
     * @param  CsvFile  $csv_file
     * @throws \Exception
     */
    private function processWithMapping(CsvFile $csv_file)
    {
        if (!Storage::disk('private')->exists($csv_file->filename)) {
            throw new \Exception('CSV file not found: ' . $csv_file->filename);
        }
        
        $file_contents = Storage::disk('private')->get($csv_file->filename);
        $csv_data = array_map('str_getcsv', explode("\n", trim($file_contents)));
        $headers = array_shift($csv_data);

        // Create entries
        foreach ($csv_data as $row) {
            // Skip empty rows
            if (empty($row) || count($row) !== count($headers)) {
                continue;
            }

            $row_data = array_combine($headers, $row);
            $entry_data = [];

            // Only process column mappings
            $columnMappings = $csv_file->columnMappings()
                ->where('mapping_type', 'column')
                ->pluck('maps_to', 'column_name')
                ->toArray();

            foreach ($columnMappings as $header => $field) {
                $value = $this->extractMappedValue($row_data, $columnMappings, $field);
                switch ($field) {
                    case 'date':
                        $entry_data['transaction_date'] = date('Y-m-d', strtotime($value));
                        break;
                    case 'amount':
                        $entry_data['amount'] = floatval(preg_replace('/[^0-9.-]/', '', $value));
                        break;
                    case 'type':
                        $entry_data['type'] = $value;
                        break;
                    case 'description':
                        $entry_data['description'] = $value;
                        break;
                    case 'recipient':
                        $entry_data['recipient'] = $value;
                        break;
                    case 'currency':
                        $entry_data['currency'] = $value;
                        break;
                }
            }

            // Set default currency to PLN if not mapped
            if (!isset($entry_data['currency'])) {
                $entry_data['currency'] = 'PLN';
            }

            $entry_data['csv_file_id'] = $csv_file->id;
            $entry_data['original_data'] = json_encode($row_data);
            
            // Create the entry
            $entry = CsvEntry::create($entry_data);

            // Find and assign categories from similar entries
            if (isset($entry_data['description'])) {
                $categoryIds = $this->findSimilarEntryCategories($entry_data['description']);
                if (!empty($categoryIds)) {
                    $entry->categories()->attach($categoryIds);
                }
            }
        }
    }

    /**
     * Extract and format values based on mapping type
     *
     * @param  array  $row_data
     * @param  array  $mappings
     * @param  string  $type
     *
     * @return mixed
     */
    private function extractMappedValue($row_data, $mappings, $type)
    {
        $column = array_search($type, $mappings);
        if (! $column) {
            return null;
        }

        $value = $row_data[$column];

        switch ($type) {
            case 'date':
                return \Carbon\Carbon::parse($value);
            case 'amount':
                return floatval(preg_replace('/[^-0-9.]/', '', $value));
            case 'currency':
                return strtoupper(trim($value));
            default:
                return $value;
        }
    }
}
