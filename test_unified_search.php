<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Http\Controllers\Api\SearchController;
use Illuminate\Http\Request;

$controller = new SearchController();
$request = new Request(['q' => 'john', 'limit' => 5]);

try {
     $response = $controller->unifiedSearch($request);
     $data = json_decode($response->getContent(), true);

     echo 'Unified Search Test:' . PHP_EOL;
     echo 'Success: ' . ($data['success'] ? 'YES' : 'NO') . PHP_EOL;
     echo 'Total Results: ' . ($data['total'] ?? 0) . PHP_EOL;

     if ($data['success'] && !empty($data['results'])) {
          echo 'Sample Results:' . PHP_EOL;
          foreach (array_slice($data['results'], 0, 3) as $result) {
               echo '- ' . ($result['nama'] ?? $result['fnama'] ?? 'N/A') . ' (' . $result['type'] . ', Score: ' . ($result['relevance_score'] ?? 0) . ')' . PHP_EOL;
          }
     }
} catch (Exception $e) {
     echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
