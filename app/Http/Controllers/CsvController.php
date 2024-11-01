<?php

namespace App\Http\Controllers;

use App\Models\CsvFile;
use App\Models\CsvEntry;
use App\Models\CsvColumnMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller for handling CSV file uploads and processing
 */
class CsvController extends Controller {

	/**
	 * Handles the upload of a CSV file
	 *
	 * @param Request $request The incoming HTTP request.
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function upload( Request $request ) {
		// 1. Validate the uploaded file
		$request->validate(
			array(
				'csv_file' => 'required|mimes:csv,txt|max:2048',
			)
		);

		try {
			DB::beginTransaction();

			$file = $request->file( 'csv_file' );

			// Store the file in the private directory
			$filename = $file->store( 'csv_files', 'local' );

			// Create CSV File record with the full path
			$csv_file = CsvFile::create(
				array(
					'filename'          => $filename,
					'original_filename' => $file->getClientOriginalName(),
					'is_mapped'         => false,
				)
			);

			// Read headers
			$csv_data = array_map( 'str_getcsv', file( $file->getPathname() ) );
			$headers  = array_shift( $csv_data );

			// Store the headers for later mapping
			$csv_file->update(
				array(
					'headers' => $headers,
				)
			);

			DB::commit();

			// Redirect to mapping page
			return redirect()
				->route( 'csv.map', $csv_file->id )
				->with( 'success', 'Please map the CSV columns' );

		} catch ( \Exception $e ) {
			DB::rollBack();
			return redirect()
				->back()
				->with( 'error', 'Error uploading CSV: ' . $e->getMessage() );
		}
	}

	/**
	 * Displays a list of uploaded CSV files
	 *
	 * @return \Illuminate\View\View
	 */
	public function index() {
		$csv_files = CsvFile::with( 'entries' )
			->latest()
			->get();

		return view( 'dashboard', compact( 'csv_files' ) );
	}

	public function showMapping( $id ) {
		$csv_file = CsvFile::findOrFail( $id );

		return view(
			'csv.map',
			array(
				'csv_file'        => $csv_file,
				'headers'         => $csv_file->headers,
				'mapping_options' => array(
					'date'        => 'Transaction Date',
					'amount'      => 'Amount',
					'description' => 'Description',
					'recipient'   => 'Recipient/Sender',
					'currency'    => 'Currency',
				),
			)
		);
	}

	public function saveMapping( Request $request, $id ) {
		$csv_file = CsvFile::findOrFail( $id );

		// Save only non-empty mappings
		foreach ( $request->mappings as $column => $maps_to ) {
			if ( ! empty( $maps_to ) ) {  // Only create mapping if a value was selected
				CsvColumnMapping::create(
					array(
						'csv_file_id' => $csv_file->id,
						'column_name' => $column,
						'maps_to'     => $maps_to,
					)
				);
			}
		}

		// Mark file as mapped
		$csv_file->update(
			array(
				'is_mapped' => true,
			)
		);

		// Now process the actual CSV data with the mappings
		$this->processWithMapping( $csv_file );

		return redirect()
			->route( 'dashboard' )
			->with( 'success', 'CSV mapped and processed successfully' );
	}

	private function processWithMapping( CsvFile $csv_file ) {
		$mappings = $csv_file->columnMappings->pluck( 'maps_to', 'column_name' )->toArray();

		$filename = str_contains( $csv_file->filename, 'csv_files/' )
			? $csv_file->filename
			: 'csv_files/' . $csv_file->filename;

		$filepath = storage_path( 'app/private/' . $filename );

		if ( ! file_exists( $filepath ) ) {
			throw new \Exception( "CSV file not found at: " . $filepath );
		}

		$csv_data = array_map( 'str_getcsv', file( $filepath ) );
		$headers  = array_shift( $csv_data );

		foreach ( $csv_data as $row ) {
			$row_data = array_combine( $headers, $row );

			CsvEntry::create(
				array(
					'csv_file_id' => $csv_file->id,
					'amount'      => $this->extractMappedValue( $row_data, $mappings, 'amount' ),
					'currency'    => $this->extractMappedValue( $row_data, $mappings, 'currency' ),
					'transaction_date' => $this->extractMappedValue( $row_data, $mappings, 'date' ),
					'description' => $this->extractMappedValue( $row_data, $mappings, 'description' ),
					'recipient' => $this->extractMappedValue( $row_data, $mappings, 'recipient' ),
					'original_data' => $row_data,
				)
			);
		}
	}

	private function extractMappedValue( $row_data, $mappings, $type ) {
		$column = array_search( $type, $mappings );
		if ( ! $column ) {
			return null;
		}

		$value = $row_data[ $column ];

		switch ( $type ) {
			case 'date':
				return \Carbon\Carbon::parse( $value );
			case 'amount':
				return floatval( preg_replace( '/[^-0-9.]/', '', $value ) );
			case 'currency':
				return strtoupper( trim( $value ) );
			default:
				return $value;
		}
	}
}
