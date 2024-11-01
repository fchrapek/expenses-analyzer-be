<?php

namespace App\Http\Controllers;

use App\Models\CsvData;
use Illuminate\Http\Request;

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

		// 2. Get the file
		$file = $request->file( 'csv_file' );

		// 3. Read CSV contents
		$csv_data = array_map( 'str_getcsv', file( $file->getPathname() ) );

		// 4. Get headers (first row)
		$headers = array_shift( $csv_data );

		// 5. Convert CSV data to array of records
		$records = array();
		foreach ( $csv_data as $row ) {
			$records[] = array_combine( $headers, $row );
		}

		// 6. Store in database
		CsvData::create(
			array(
				'filename' => $file->getClientOriginalName(),
				'data'     => $records,
			)
		);

		// 7. Redirect back with success message
		return redirect()
			->back()
			->with( 'success', 'CSV file uploaded and processed successfully' );
	}

	/**
	 * Displays a list of uploaded CSV files
	 *
	 * @return \Illuminate\View\View
	 */
	public function index() {
		$csv_files = CsvData::latest()->get();
		return view( 'dashboard', compact( 'csv_files' ) );
	}
}
