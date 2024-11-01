<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create(
			'csv_data',
			function ( Blueprint $table ) {
				$table->id();                    // Auto-incrementing ID
				$table->string( 'filename' );      // Name of uploaded file
				$table->json( 'data' );            // CSV contents as JSON
				$table->timestamps();            // Created/Updated timestamps
			}
		);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists( 'csv_data' );
	}
};
