<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = \App\Models\User::where('role', 'admin')->first();
auth()->login($admin);

$controller = new \App\Http\Controllers\KonfirmasiGmsController();

// Test store Alpha
$request = \Illuminate\Http\Request::create('/konfirmasi-gms', 'POST', [
    'user_id' => 23,
    'tanggal' => '2026-10-19',
    'status' => 'Izin',
    'keterangan' => 'Izin dinas luar kota',
]);

$response = $controller->store($request);
echo "Store response: OK\n";

$saved = \App\Models\AbsensiGms::where('user_id', 23)->whereDate('tanggal', '2026-10-19')->first();
echo "Saved record: Status={$saved->status}, Keterangan={$saved->keterangan}, ConfirmedBy={$saved->confirmedBy->name}\n";

// Re-check Index
$requestIndex = \Illuminate\Http\Request::create('/konfirmasi-gms?tanggal=2026-10-19', 'GET');
$resIndex = $controller->index($requestIndex);
$props = $resIndex->toResponse($requestIndex)->original->getData()['page']['props'];
echo "Updated Stats: " . json_encode($props['stats']) . "\n";
echo "Teacher status: " . $props['teachers'][0]['status'] . ", is_manual: " . ($props['teachers'][0]['is_manual'] ? 'YES' : 'NO') . "\n";

// Clean up test record
$saved->delete();
echo "Cleaned test record.\n";
