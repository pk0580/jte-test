<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
$baseUrl = 'http://localhost:8080/api/v1/orders/search'; // Предположим такой эндпоинт на основе README (нужно проверить маршруты)

$queries = ['test', 'order', 'client', 'search'];
$totalRequests = 50;
$successCount = 0;
$errorCount = 0;
$latencies = [];

echo "Starting load test with $totalRequests requests...\n";

for ($i = 0; $i < $totalRequests; $i++) {
    $query = $queries[array_rand($queries)];
    $start = microtime(true);
    try {
        // Здесь мы имитируем вызов API. Если эндпоинта нет, мы можем вызвать сервис напрямую через Symfony Kernel
        // Но для "нагрузочного теста" в контейнере проще дергать HTTP если он есть.
        // Если API нет, попробуем вызвать напрямую через bin/console если есть такая команда
        $response = $client->request('GET', "http://nginx/api/v1/orders/search?query=" . urlencode($query));
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            echo "Status: " . $statusCode . " Response: " . $response->getContent(false) . "\n";
        }

        $duration = (microtime(true) - $start) * 1000;
        $latencies[] = $duration;

        if ($statusCode === 200) {
            $successCount++;
        } else {
            $errorCount++;
        }
    } catch (\Exception $e) {
        $errorCount++;
        echo "Error: " . $e->getMessage() . "\n";
    }
}

$avgLatency = array_sum($latencies) / count($latencies);
echo "\nResults:\n";
echo "Total Requests: $totalRequests\n";
echo "Success: $successCount\n";
echo "Errors: $errorCount\n";
echo "Average Latency: " . round($avgLatency, 2) . "ms\n";
