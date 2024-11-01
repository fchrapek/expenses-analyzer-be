<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model representing CSV data with filename and parsed content
 */
class CsvData extends Model {

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<string>
	 */
	protected $fillable = array(
		'filename',
		'data',
	);

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array<string, string>
	 */
	protected $casts = array(
		'data' => 'array',
	);
}
