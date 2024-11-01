<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvFile extends Model {

	protected $fillable = array(
		'filename',
		'original_filename',
		'total_entries',
		'is_mapped',
		'headers',
	);

	protected $casts = array(
		'headers' => 'array',
		'is_mapped' => 'boolean',
	);

	public function entries() {
		return $this->hasMany( CsvEntry::class );
	}

	public function columnMappings() {
		return $this->hasMany( CsvColumnMapping::class );
	}
}
