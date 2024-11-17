<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class AddFoodCategory extends Command
{
    protected $signature = 'category:add-food';
    protected $description = 'Add the Food category to the categories table';

    public function handle()
    {
        if (Category::where('name', 'Food')->exists()) {
            $this->info('Food category already exists.');
            return;
        }

        Category::create([
            'name' => 'Food',
            'color' => '#8BC34A',  // Light Green
            'exclude_from_calculations' => false
        ]);

        $this->info('Food category added successfully.');
    }
}
