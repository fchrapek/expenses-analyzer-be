<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvColumnMapping extends Model {

	protected $fillable = array(
		'csv_file_id',
		'column_name',
		'maps_to',
	);

	public function csvFile() {
		return $this->belongsTo( CsvFile::class );
	}
}
