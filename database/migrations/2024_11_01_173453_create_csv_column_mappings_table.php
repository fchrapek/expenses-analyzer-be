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
			'csv_column_mappings',
			function ( Blueprint $table ) {
				$table->id();
				$table->foreignId( 'csv_file_id' )->constrained()->onDelete( 'cascade' );
				$table->string( 'column_name' );     // Original CSV column name
				$table->string( 'maps_to' );         // What this column represents (date/amount/description)
				$table->timestamps();
			}
		);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists( 'csv_column_mappings' );
	}
};
