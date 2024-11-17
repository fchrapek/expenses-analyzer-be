<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;

return new class extends Migration
{
    public function up(): void
    {
        if (!Category::where('name', 'Food')->exists()) {
            Category::create([
                'name' => 'Food',
                'color' => '#8BC34A',  // Light Green
                'exclude_from_calculations' => false
            ]);
        }
    }

    public function down(): void
    {
        Category::where('name', 'Food')->delete();
    }
};
