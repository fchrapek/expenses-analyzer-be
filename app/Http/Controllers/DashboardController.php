<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\CsvFile;

class DashboardController extends Controller
{
    public function index()
    {
        $csv_files = CsvFile::with(['entries.categories'])
            ->latest()
            ->get();

        // Add this line to debug
        \Log::info('CSV Files:', ['data' => $csv_files->toArray()]);

        return Inertia::render('Dashboard', [
            'csv_files' => $csv_files
        ]);
    }
}
