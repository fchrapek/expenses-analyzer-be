# Expenses Analyzer

## Features

### Current Features ✅
- Secure CSV file upload and storage
- Flexible column mapping system
- Intelligent transaction grouping
- Support for multiple transaction fields:
  - Transaction date
  - Amount
  - Description
  - Recipient/Sender
  - Currency
- Collapsible grouped transactions view

### Upcoming Features 🚀
- Transaction categorization
- Spending analysis
- Data visualization
- Budget tracking
- Export capabilities


## Database Structure

### Core Tables

csv_files

```
Schema::create('csv_files', function (Blueprint $table) {
    $table->id();
    $table->string('file_name');
    $table->string('original_file_name');
    $table->integer('total_entries')->default(0);
    $table->boolean('is_mapped')->default(false);
    $table->json('csv_headers')->nullable();
    $table->timestamps();
});
```

csv_entries

```
Schema::create('csv_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('csv_file_id');
    $table->decimal('amount', 10, 2);
    $table->string('transaction_currency')->nullable();
    $table->date('transaction_date');
    $table->string('transaction_description');
    $table->string('recipient_name')->nullable();
    $table->json('original_data');
    $table->timestamps();
});
```

csv_column_mappings

```
Schema::create('csv_column_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('csv_file_id');
    $table->string('original_column_name');
    $table->string('mapped_to_field');
    $table->timestamps();
});
```

## Core Components

### File Upload System

```
public function upload_csv(Request $request) {
    $validation_rules = [
        'csv_file' => 'required|mimes:csv,txt|max:2048'
    ];
    
    $file = $request->file('csv_file');
    $file_path = $file->store('csv_files', 'local');
}
```

### Transaction Grouping

```
public function get_grouped_entries() {
    $entries = $this->csv_file->entries()
        ->order_by('transaction_date')
        ->get();

    $grouped_entries = [];
    $processed_ids = [];
    
    // Group similar transactions based on description and recipient
    foreach ($entries as $entry) {
        // Grouping logic implementation
    }
}
```

### String Similarity Helper

```
class StringHelper {
    public static function get_similarity($string_one, $string_two) {
        $cleaned_string_one = preg_replace('/[0-9]+/', '', $string_one);
        $cleaned_string_two = preg_replace('/[0-9]+/', '', $string_two);
        s
        similar_text($cleaned_string_one, $cleaned_string_two, $percentage);
        return $percentage;
    }
}
```
