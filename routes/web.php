<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CsvController;
use Illuminate\Support\Facades\Route;

Route::get(
	'/',
	function () {
		return view( 'welcome' );
	}
);

Route::get(
	'/dashboard',
	array( CsvController::class, 'index' )
)->middleware( array( 'auth', 'verified' ) )->name( 'dashboard' );

Route::middleware( 'auth' )->group(
	function () {
		Route::get( '/profile', array( ProfileController::class, 'edit' ) )->name( 'profile.edit' );
		Route::patch( '/profile', array( ProfileController::class, 'update' ) )->name( 'profile.update' );
		Route::delete( '/profile', array( ProfileController::class, 'destroy' ) )->name( 'profile.destroy' );
	}
);

Route::post( '/csv/upload', array( CsvController::class, 'upload' ) )->name( 'csv.upload' );

require __DIR__ . '/auth.php';
