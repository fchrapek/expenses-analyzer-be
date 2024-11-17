<?php

namespace App\Models;

// Laravel core imports
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
// Application models
use App\Models\CsvEntry;
use App\Models\CsvColumnMapping;

/**
 * CsvFile Model
 *
 * Represents a CSV file in the system with its metadata and relationships.
 * This model connects to the 'csv_files' table (Laravel convention).
 */
class CsvFile extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * This protects against mass-assignment vulnerabilities.
     * Only these fields can be filled using create() or fill() methods.
     *
     * Example usage:
     * CsvFile::create([
     *     'filename' => 'path/to/file.csv',
     *     'original_filename' => 'sales_data.csv'
     * ]);
     *
     * @var array<string>
     */
    protected $fillable = [
        'filename',           // Stored filename in the system
        'original_filename',  // Original name of uploaded file
        'total_entries',     // Count of entries in the CSV
        'is_mapped',         // Whether columns are mapped
        'headers',           // CSV header columns
    ];

    /**
     * The attributes that should be cast.
     *
     * Automatically converts database values to/from PHP types.
     *
     * Example:
     * - Database stores headers as JSON string: ["date", "amount"]
     * - In PHP, accessed as array: ['date', 'amount']
     *
     * @var array<string, string>
     */
    protected $casts = [
        'headers' => 'array',    // JSON in DB ↔ Array in PHP
        'is_mapped' => 'boolean' // 0/1 in DB ↔ false/true in PHP
    ];

    /**
     * Get all entries for this CSV file
     *
     * Defines a one-to-many relationship with CsvEntry model.
     * One CSV file can have multiple entries.
     *
     * Shorthand version using Laravel conventions.
     *
     * Extended version would be:
     * return $this->hasMany(
     *     CsvEntry::class,      // Related model
     *     'csv_file_id',        // Foreign key (automatically assumed)
     *     'id'                  // Local key (automatically assumed)
     * );
     *
     * Example usage:
     * $csv_file->entries->each(function ($entry) {
     *     echo $entry->amount;
     * });
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CsvEntry::class);
    }

    /**
     * Get all column mappings for this CSV file
     *
     * Defines a one-to-many relationship with CsvColumnMapping model.
     * One CSV file can have multiple column mappings.
     *
     * Shorthand version using Laravel conventions.
     *
     * Extended version would be:
     * return $this->hasMany(
     *     CsvColumnMapping::class,    // Related model
     *     'csv_file_id',             // Foreign key (automatically assumed)
     *     'id'                       // Local key (automatically assumed)
     * );
     *
     * Example usage:
     * $csv_file->columnMappings->each(function ($mapping) {
     *     echo $mapping->column_name;
     * });
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function columnMappings(): HasMany
    {
        return $this->hasMany(CsvColumnMapping::class);
    }
}
