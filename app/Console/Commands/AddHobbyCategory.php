<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class AddHobbyCategory extends Command
{
    protected $signature = 'category:add-hobby';
    protected $description = 'Add the Hobby category to the categories table';

    public function handle()
    {
        if (Category::where('name', 'Hobby')->exists()) {
            $this->info('Hobby category already exists.');
            return;
        }

        Category::create([
            'name' => 'Hobby',
            'color' => '#673AB7',  // Deep Purple
            'exclude_from_calculations' => false
        ]);

        $this->info('Hobby category added successfully.');
    }
}
