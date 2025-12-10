<?php

ini_set('memory_limit', '512M');

// Script migrasi data dari SQLite ke MySQL
// Jalankan dengan: php migrate_data.php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

// Function to clean invalid UTF-8 characters
function cleanData($data)
{
    if (is_string($data)) {
        // Remove invalid UTF-8 sequences
        return iconv('UTF-8', 'UTF-8//IGNORE', $data);
    } elseif (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = cleanData($value);
        }
    }
    return $data;
}

// Setup SQLite connection
Config::set('database.connections.sqlite.database', database_path('database.sqlite'));

echo "🚀 Memulai migrasi data dari SQLite ke MySQL...\n";

$tables = [
    'users',
    'pembiayaans',
    'tabungans',
    'depositos',
    'linkages',
    'financial_highlights',
    'email_pin_codes'
];

foreach ($tables as $table) {
    try {
        echo "\n📊 Migrating table: {$table}\n";

        // Get MySQL table columns
        $mysqlColumns = DB::connection('mysql')->select("DESCRIBE {$table}");
        $mysqlColumnNames = array_column($mysqlColumns, 'Field');

        // Get total count
        $totalCount = DB::connection('sqlite')->table($table)->count();
        echo "   Total records: {$totalCount}\n";

        if ($totalCount == 0) {
            echo "   ℹ️  No data to migrate\n";
            continue;
        }

        // Migrate in batches
        $batchSize = 100; // Reduced batch size
        $offset = 0;
        $migrated = 0;

        while ($offset < $totalCount) {
            $records = DB::connection('sqlite')
                ->table($table)
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            if ($records->count() == 0) break;

            $data = $records->map(function ($item) use ($mysqlColumnNames) {
                $itemArray = (array) $item;
                // Filter to only include columns that exist in MySQL
                $itemArray = array_intersect_key($itemArray, array_flip($mysqlColumnNames));
                // Clean invalid UTF-8 characters
                $itemArray = cleanData($itemArray);
                return $itemArray;
            })->toArray();

            if (!empty($data)) {
                try {
                    DB::connection('mysql')->table($table)->insertOrIgnore($data);
                    $migrated += count($data);
                } catch (Exception $e) {
                    echo "   ❌ Insert error for {$table}: " . $e->getMessage() . "\n";
                    // Remove data sample to save memory
                    break; // Stop on first error to avoid memory issues
                }
            }

            $offset += $batchSize;
        }

        echo "   🎉 Table {$table} completed: {$migrated} records migrated\n";
    } catch (Exception $e) {
        echo "   ❌ Error migrating {$table}: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Migrasi data selesai!\n";

// Verification
echo "\n📊 Verifikasi data:\n";
foreach ($tables as $table) {
    try {
        $mysqlCount = DB::connection('mysql')->table($table)->count();
        echo "   {$table}: {$mysqlCount} records\n";
    } catch (Exception $e) {
        echo "   {$table}: Error - " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Migrasi database SQLite → MySQL SELESAI!\n";
