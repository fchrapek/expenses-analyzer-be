<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CsvEntry;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories
     */
    public function index()
    {
        return Category::all();
    }

    /**
     * Assign category to entry/entries
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'entry_ids' => 'required|array',
            'entry_ids.*' => 'exists:csv_entries,id',
            'category_id' => 'required|exists:categories,id'
        ]);

        // Replace existing categories with new one
        CsvEntry::whereIn('id', $validated['entry_ids'])
            ->each(function ($entry) use ($validated) {
                $entry->categories()->sync([$validated['category_id']]);
            });

        // Return the category with its relationships
        $category = Category::find($validated['category_id']);

        return response()->json([
            'message' => 'Category assigned successfully',
            'category' => $category
        ]);
    }
}
