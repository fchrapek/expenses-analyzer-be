<?php

namespace App\Models;

use App\Helpers\StringHelper;
use Illuminate\Database\Eloquent\Model;

class CsvEntry extends Model {

	protected $fillable = array(
		'csv_file_id',
		'amount',
		'currency',
		'transaction_date',
		'description',
		'recipient',
		'original_data',
	);

	protected $casts = array(
		'original_data'    => 'array',
		'transaction_date' => 'date',
		'amount'           => 'decimal:2',
	);

	public function csvFile() {
		return $this->belongsTo( CsvFile::class );
	}

	public function getGroupedEntries() {
		$entries = $this->csvFile->entries()
			->orderBy( 'transaction_date' )
			->get();

		$groups        = array();
		$processed     = array();
		$singleEntries = array();

		foreach ( $entries as $entry ) {
			if ( in_array( $entry->id, $processed ) ) {
				continue;
			}

			$group = array(
				'main_entry'      => $entry,
				'similar_entries' => array(),
				'total_amount'    => $entry->amount,
			);

			// Find similar entries
			foreach ( $entries as $compareEntry ) {
				if ( $entry->id === $compareEntry->id || in_array( $compareEntry->id, $processed ) ) {
					continue;
				}

				if ( $this->areEntriesSimilar( $entry, $compareEntry ) ) {
					$group['similar_entries'][] = $compareEntry;
					$group['total_amount']     += $compareEntry->amount;
					$processed[]                = $compareEntry->id;
				}
			}

			if ( ! empty( $group['similar_entries'] ) ) {
				$groups[] = $group;
			} else {
				$singleEntries[] = array(
					'main_entry'      => $entry,
					'similar_entries' => array(),
					'total_amount'    => $entry->amount,
				);
			}

			$processed[] = $entry->id;
		}

		// Sort groups by total amount
		usort(
			$groups,
			function ( $a, $b ) {
				return abs( $b['total_amount'] ) - abs( $a['total_amount'] );
			}
		);

		// Return grouped entries first, followed by single entries
		return array_merge( $groups, $singleEntries );
	}

	private function areEntriesSimilar( $entry1, $entry2 ): bool {
		// Must have same recipient
		if ( $entry1->recipient !== $entry2->recipient ) {
			return false;
		}

		// Check description similarity
		$similarity = StringHelper::getSimilarity( $entry1->description, $entry2->description );

		return $similarity > 80; // Adjust this threshold as needed
	}
}
