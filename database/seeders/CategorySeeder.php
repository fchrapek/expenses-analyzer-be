<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Exclude',
                'color' => '#FF4444',  // Red
                'exclude_from_calculations' => true
            ],
            [
                'name' => 'Eating Out',
                'color' => '#FF9800',  // Orange
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'Medical',
                'color' => '#2196F3',  // Blue
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'Self-Care',
                'color' => '#E91E63',  // Pink
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'Pets',
                'color' => '#9C27B0',  // Purple
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'Insurance',
                'color' => '#607D8B',  // Blue Grey
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'Investing',
                'color' => '#4CAF50',  // Green
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'Car',
                'color' => '#795548',  // Brown
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'House',
                'color' => '#009688',  // Teal
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'Phone',
                'color' => '#3F51B5',  // Indigo
                'exclude_from_calculations' => false
            ],
            [
                'name' => 'Others',
                'color' => '#9E9E9E',  // Grey
                'exclude_from_calculations' => false
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
