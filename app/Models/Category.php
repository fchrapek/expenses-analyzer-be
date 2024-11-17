<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'color',
        'exclude_from_calculations'
    ];

    protected $casts = [
        'exclude_from_calculations' => 'boolean',
    ];

    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(CsvEntry::class, 'category_csv_entry');
    }
}
