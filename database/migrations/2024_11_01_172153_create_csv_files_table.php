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
			'csv_files',
			function ( Blueprint $table ) {
				$table->id();
				$table->string( 'filename' );
				$table->string( 'original_filename' );
				$table->integer( 'total_entries' )->default( 0 );
				$table->boolean( 'is_mapped' )->default( false );
				$table->json( 'headers' )->nullable();
				$table->timestamps();
			}
		);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists( 'csv_files' );
	}
};
