<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CsvFile;

/**
 * CsvColumnMapping Model
 *
 * Represents the mapping between CSV columns and their interpreted meanings.
 * Each mapping belongs to a specific CSV file and defines how a column
 * should be interpreted (e.g., which column contains dates, amounts, etc.).
 */
class CsvColumnMapping extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * Allows these fields to be mass-assigned using create() or fill().
     *
     * Example usage:
     * CsvColumnMapping::create([
     *     'csv_file_id' => 1,
     *     'column_name' => 'Transaction Date',
     *     'maps_to' => 'date'
     * ]);
     *
     * @var array<string>
     */
    protected $fillable = [
        'csv_file_id',
        'column_name',
        'maps_to',
    ];

    /**
     * Get the CSV file that owns this column mapping
     *
     * Defines an inverse one-to-many relationship with CsvFile model.
     * Each column mapping belongs to one CSV file.
     *
     * Shorthand version using Laravel conventions.
     *
     * Extended version would be:
     * return $this->belongsTo(
     *     CsvFile::class,      // Related model
     *     'csv_file_id',       // Foreign key on this table (automatically assumed)
     *     'id'                 // Primary key on parent table (automatically assumed)
     * );
     *
     * Example usage:
     * $mapping->csvFile->filename;  // Get parent file's filename
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function csvFile(): BelongsTo
    {
        return $this->belongsTo(CsvFile::class);
    }
}
