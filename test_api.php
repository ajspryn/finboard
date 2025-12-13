<?php

echo "Testing API endpoints on port 8001...\n";

// Test deposito detail
$url = 'http://127.0.0.1:8001/api/search/deposito/1';
echo "Testing: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "CURL Error: $curlError\n";
} else {
    echo "HTTP Code: $httpCode\n";
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ API Success! Deposito detail retrieved.\n";
            echo "Nama: " . ($data['data']['nama'] ?? 'N/A') . "\n";
            echo "Nodep: " . ($data['data']['nodep'] ?? 'N/A') . "\n";
        } else {
            echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ HTTP Error: $httpCode\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\nTesting unified search...\n";
$url2 = 'http://127.0.0.1:8001/api/search/unified?q=BJB';
echo "Testing: $url2\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curlError2 = curl_error($ch2);
curl_close($ch2);

if ($curlError2) {
    echo "CURL Error: $curlError2\n";
} else {
    echo "HTTP Code: $httpCode2\n";
    if ($httpCode2 == 200) {
        $data2 = json_decode($response2, true);
        if ($data2 && isset($data2['success']) && $data2['success']) {
            echo "✅ Unified Search Success! Found " . count($data2['results']) . " results.\n";
            $typeCount = [];
            foreach ($data2['results'] as $result) {
                $type = $result['type'];
                if (!isset($typeCount[$type])) $typeCount[$type] = 0;
                $typeCount[$type]++;
            }
            foreach ($typeCount as $type => $count) {
                echo "  - $type: $count results\n";
            }
        } else {
            echo "❌ Search Error: " . ($data2['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ HTTP Error: $httpCode2\n";
    }
}
