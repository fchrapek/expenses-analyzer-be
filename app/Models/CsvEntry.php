<?php

namespace App\Models;

// Laravel imports
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// Application imports
use App\Helpers\StringHelper;
use App\Models\CsvFile;
use App\Models\Category;

/**
 * CsvEntry Model
 *
 * Represents a single entry/row from a CSV file.
 * Each entry contains transaction details like amount, date, description, etc.
 * Entries can be grouped based on similarity for analysis.
 */
class CsvEntry extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * Example usage:
     * CsvEntry::create([
     *     'csv_file_id' => 1,
     *     'amount' => 100.50,
     *     'description' => 'Monthly Payment'
     * ]);
     *
     * @var array<string>
     */
    protected $fillable = [
        'csv_file_id',      // ID of parent CSV file
        'amount',           // Transaction amount
        'currency',         // Currency code (USD, EUR, etc)
        'transaction_date', // Date of transaction
        'description',      // Transaction description
        'recipient',        // Recipient/sender name
        'type',             // Transaction type
        'original_data',    // Original row data as array
    ];

    /**
     * The attributes that should be cast.
     *
     * Automatically converts database values to/from PHP types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'original_data'    => 'array',   // JSON in DB ↔ Array in PHP
        'transaction_date' => 'date',    // String in DB ↔ Carbon in PHP
        'amount'          => 'decimal:2', // String in DB ↔ Decimal in PHP
    ];

    /**
     * Get the CSV file that owns this entry
     *
     * Defines an inverse one-to-many relationship with CsvFile model.
     * Each entry belongs to one CSV file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function csvFile(): BelongsTo
    {
        return $this->belongsTo(CsvFile::class);
    }

    /**
     * Group entries first by type, then by similarity
     *
     * Returns array of type groups, each containing:
     * - type: The transaction type
     * - groups: Array of similar entry groups
     * - total_amount: Sum of all entries in this type
     *
     * @return array
     */
    public function getGroupedEntries(): array
    {
        $entries = $this->csvFile->entries()
            ->orderBy('transaction_date')
            ->get();

        // First, group by type
        $typeGroups = [];
        foreach ($entries as $entry) {
            $type = $entry->type ?? 'uncategorized';
            if (!isset($typeGroups[$type])) {
                $typeGroups[$type] = [
                    'type' => $type,
                    'entries' => [],
                    'total_amount' => 0,
                ];
            }
            $typeGroups[$type]['entries'][] = $entry;
            $typeGroups[$type]['total_amount'] += $entry->amount;
        }

        // Then, within each type, group by similarity
        $result = [];
        foreach ($typeGroups as $typeGroup) {
            $similarityGroups = $this->groupEntriesBySimilarity($typeGroup['entries']);

            $result[] = [
                'type' => $typeGroup['type'],
                'groups' => $similarityGroups,
                'total_amount' => $typeGroup['total_amount'],
            ];
        }

        // Sort type groups by absolute total amount
        usort($result, function ($a, $b) {
            return abs($b['total_amount']) - abs($a['total_amount']);
        });

        return $result;
    }

    /**
     * Group entries by similarity
     *
     * @param array $entries
     * @return array
     */
    private function groupEntriesBySimilarity($entries): array
    {
        $groups = [];
        $processed = [];
        $singleEntries = [];

        foreach ($entries as $entry) {
            if (in_array($entry->id, $processed)) {
                continue;
            }

            $group = [
                'main_entry' => $entry,
                'similar_entries' => [],
                'total_amount' => $entry->amount,
            ];

            foreach ($entries as $compareEntry) {
                if ($entry->id === $compareEntry->id || in_array($compareEntry->id, $processed)) {
                    continue;
                }

                if ($this->areEntriesSimilar($entry, $compareEntry)) {
                    $group['similar_entries'][] = $compareEntry;
                    $group['total_amount'] += $compareEntry->amount;
                    $processed[] = $compareEntry->id;
                }
            }

            if (!empty($group['similar_entries'])) {
                $groups[] = $group;
            } else {
                $singleEntries[] = [
                    'main_entry' => $entry,
                    'similar_entries' => [],
                    'total_amount' => $entry->amount,
                ];
            }

            $processed[] = $entry->id;
        }

        // Sort groups by absolute total amount
        usort($groups, function ($a, $b) {
            return abs($b['total_amount']) - abs($a['total_amount']);
        });

        return array_merge($groups, $singleEntries);
    }

    /**
     * Check if two entries are similar based on recipient and description
     *
     * Entries are considered similar if they:
     * 1. Have the same recipient
     * 2. Have descriptions with >80% similarity
     *
     * @param CsvEntry $entry1
     * @param CsvEntry $entry2
     * @return bool
     */
    private function areEntriesSimilar($entry1, $entry2): bool
    {
        // Must have same recipient
        if ($entry1->recipient !== $entry2->recipient) {
            return false;
        }

        // Check description similarity using helper
        $similarity = StringHelper::getSimilarity(
            $entry1->description,
            $entry2->description
        );

        return $similarity > 80; // Similarity threshold
    }

    /**
     * Get categories related to this entry
     *
     * Defines a many-to-many relationship with Category model.
     * Each entry can have multiple categories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_csv_entry');
    }

    /**
     * Check if entry should be excluded from calculations
     */
    public function shouldExclude(): bool
    {
        return $this->categories()
            ->where('exclude_from_calculations', true)
            ->exists();
    }

    /**
     * Get amount considering exclusion
     */
    public function getCalculableAmount(): float
    {
        return $this->shouldExclude() ? 0 : $this->amount;
    }

    /**
     * Check if entry is uncategorized
     */
    public function isUncategorized(): bool
    {
        return $this->categories->isEmpty();
    }

    /**
     * Get entry status for display
     */
    public function getStatusClass(): string
    {
        if ($this->shouldExclude()) {
            return 'line-through text-gray-400';
        }
        if ($this->isUncategorized()) {
            return 'text-yellow-600';  // Highlight uncategorized amounts
        }
        return '';
    }
}
