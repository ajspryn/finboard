<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = new Illuminate\Http\Request();
$request->merge(["q" => "2025", "limit" => 10]);

$controller = new App\Http\Controllers\Api\SearchController();
$result = $controller->unifiedSearch($request);

echo "Total results: " . $result["total"] . PHP_EOL;
echo "Results:" . PHP_EOL;
foreach ($result["results"] as $item) {
    echo "- " . $item["type"] . ": " . ($item["nama"] ?? $item["period"] ?? "N/A") . PHP_EOL;
}

