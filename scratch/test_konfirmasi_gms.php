<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = \App\Models\User::where('role', 'admin')->first();
auth()->login($admin);

$request = \Illuminate\Http\Request::create('/konfirmasi-gms', 'GET');
$response = $app->handle($request);

echo "Status Code for /konfirmasi-gms: " . $response->getStatusCode() . "\n";
$controller = new \App\Http\Controllers\KonfirmasiGmsController();
$inertiaResponse = $controller->index($request);
$props = $inertiaResponse->toResponse($request)->original->getData()['page']['props'];

echo "Selected Date: " . $props['selectedDate'] . " (" . $props['namaHari'] . ")\n";
echo "Total Teachers Scheduled: " . count($props['teachers']) . "\n";
echo "Stats: " . json_encode($props['stats'], JSON_PRETTY_PRINT) . "\n";
