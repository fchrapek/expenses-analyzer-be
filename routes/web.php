<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CsvController;
use App\Http\Controllers\CategoryController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| CSV File Management
|--------------------------------------------------------------------------
|
| These routes handle the upload, processing, and deletion of CSV files.
| The processing includes parsing entries and creating relationships.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    /**
     * Dashboard View
     * URL: /dashboard
     * Method: GET
     * Purpose: Display main dashboard with uploaded files and entries
     */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /**
     * CSV File Upload
     * URL: /csv/upload
     * Method: POST
     * Purpose: Handle CSV file upload and initial validation
     * Expects: Form Data with 'csv_file' field
     */
    Route::post('/csv/upload', [CsvController::class, 'upload'])->name('csv.upload');

    /**
     * CSV Mapping Interface
     * URL: /csv/{id}/map
     * Method: GET
     * Purpose: Show interface for mapping CSV columns
     * Parameters:
     *   - id: The ID of the uploaded CSV file
     * Shows: Column mapping form with preview of CSV data
     */
    Route::get('/csv/{csvFile}/map', [CsvController::class, 'showMapping'])->name('csv.map');
    Route::post('/csv/{csvFile}/map', [CsvController::class, 'saveMapping'])->name('csv.map.save');
    Route::get('/csv/{csvFile}/view', [CsvController::class, 'show'])->name('csv.view');

    /**
     * CSV Processing
     * URL: /csv/process/{csvFile}
     * Method: POST
     * Purpose: Process mapped CSV data and create entries
     * Parameters:
     *   - csvFile: The CSV file model instance
     * Expects: JSON with column mapping data
     */
    Route::post('/csv/process/{csvFile}', [CsvController::class, 'process'])->name('csv.process');

    /**
     * CSV File Deletion
     * URL: /csv/{csvFile}
     * Method: DELETE
     * Purpose: Remove CSV file and related entries
     * Parameters:
     *   - csvFile: The CSV file to delete
     * Cascades: Deletes related entries and category assignments
     */
    Route::delete('/csv/{csvFile}', [CsvController::class, 'destroy'])->name('csv.destroy');

    Route::post('/assign-category', [CategoryController::class, 'assignToEntries'])->name('category.assign');
});

/*
|--------------------------------------------------------------------------
| Category Management
|--------------------------------------------------------------------------
|
| Routes for managing categories and their assignments to entries.
| Includes API endpoints for real-time updates.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    /**
     * Category List
     * URL: /api/categories
     * Method: GET
     * Purpose: Retrieve all available categories
     * Returns: JSON array of categories with colors and exclusion status
     */
    Route::get('/api/categories', [CategoryController::class, 'index'])->name('categories.index');

    /**
     * Category Assignment
     * URL: /assign-category
     * Method: POST
     * Purpose: Assign category to one or more entries
     * Expects: JSON with entry_ids and category_id
     * Returns: Updated category information
     */
    Route::post('/assign-category', [CategoryController::class, 'assign'])->name('categories.assign');
});

require __DIR__.'/auth.php';
