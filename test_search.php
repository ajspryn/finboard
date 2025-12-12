<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Tabungan;

$results = Tabungan::search('john', ['size' => 2]);
echo 'Search results structure:' . PHP_EOL;
var_export($results);
echo PHP_EOL;

if (!empty($results['hits'])) {
     echo 'First hit details:' . PHP_EOL;
     var_export($results['hits'][0]);
     echo PHP_EOL;
}
