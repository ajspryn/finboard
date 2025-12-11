<?php
require 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

echo '=== TESTING CONTROLLER VALIDATION ===' . PHP_EOL;

// Create mock request with wrong file type
$request = new Request();
$request->merge([
    'month' => '12',
    'year' => '2025',
    'upload_types' => ['tabungan'] // User selects tabungan
]);

// Create mock file upload - use pembiayaan file but pretend it's tabungan
$pembiayaanFilePath = '/Users/ajspryn/Project/finboard/test-data/small_pembiayaan_test.csv';
$uploadedFile = new UploadedFile($pembiayaanFilePath, 'test.csv', 'text/csv', null, true);

// Add file to request
$files = ['csv_tabungan' => $uploadedFile];
$request->files->add($files);

$controller = new App\Http\Controllers\UploadController();

// Test validation method
try {
    $errors = $controller->validateCsvFileTypes($request, ['tabungan']);
    
    if (!empty($errors)) {
        echo '✅ SUCCESS: Validation caught wrong file type' . PHP_EOL;
        foreach ($errors as $error) {
            echo 'Error: ' . $error . PHP_EOL;
        }
    } else {
        echo '❌ FAILED: Validation should have caught wrong file type' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test with correct file type
$request2 = new Request();
$request2->merge([
    'month' => '12',
    'year' => '2025',
    'upload_types' => ['pembiayaan'] // User selects pembiayaan
]);

$files2 = ['csv_file' => $uploadedFile];
$request2->files->add($files2);

try {
    $errors2 = $controller->validateCsvFileTypes($request2, ['pembiayaan']);
    
    if (empty($errors2)) {
        echo '✅ SUCCESS: Validation passed for correct file type' . PHP_EOL;
    } else {
        echo '❌ FAILED: Validation should have passed for correct file type' . PHP_EOL;
        foreach ($errors2 as $error) {
            echo 'Error: ' . $error . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage() . PHP_EOL;
}
