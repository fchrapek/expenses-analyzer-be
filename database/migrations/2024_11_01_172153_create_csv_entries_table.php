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
			'csv_entries',
			function ( Blueprint $table ) {
				$table->id();
				$table->foreignId( 'csv_file_id' )->constrained()->onDelete( 'cascade' );
				$table->decimal( 'amount', 10, 2 );
				$table->string( 'currency' )->nullable();
				$table->date( 'transaction_date' );
				$table->string( 'description' );
				$table->string( 'recipient' )->nullable();
				$table->json( 'original_data' );
				$table->timestamps();
			}
		);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists( 'csv_entries' );
	}
};
