<?php

/**
 * SiMonitorGuru - Independent Maintenance Web Gate
 * 
 * Menangani kontrol mode DOWN dan UP langsung dari browser
 * TANPA melalui file routes/web.php sama sekali (Bypass Router).
 */

// Baca URI yang diminta
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

// Konfigurasi default
$downPath = '/system-down';
$upPath = '/system-up';
$secretKey = 'simonitorguru2026';

// Baca konfigurasi dari .env jika tersedia
$envFile = dirname(dirname(__DIR__)) . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^MAINTENANCE_DOWN_PATH=(.*)$/m', $envContent, $m)) {
        $val = trim($m[1], " \t\n\r\0\x0B\"'");
        if (!empty($val)) $downPath = $val;
    }
    if (preg_match('/^MAINTENANCE_UP_PATH=(.*)$/m', $envContent, $m)) {
        $val = trim($m[1], " \t\n\r\0\x0B\"'");
        if (!empty($val)) $upPath = $val;
    }
    if (preg_match('/^MAINTENANCE_SECRET_KEY=(.*)$/m', $envContent, $m)) {
        $val = trim($m[1], " \t\n\r\0\x0B\"'");
        if (!empty($val)) $secretKey = $val;
    }
}

// Pastikan diawali slash
if (!str_starts_with($downPath, '/')) $downPath = '/' . $downPath;
if (!str_starts_with($upPath, '/')) $upPath = '/' . $upPath;

// Jika path bukan path maintenance, kembalikan kontrol ke alur normal Laravel
if ($path !== $downPath && $path !== $upPath) {
    return;
}

// Validasi secret key (?key=...)
$inputKey = $_GET['key'] ?? '';
if (empty($inputKey) || !hash_equals($secretKey, $inputKey)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p></body></html>';
    exit;
}

// Inisialisasi framework untuk menjalankan Artisan
$projectRoot = dirname(dirname(__DIR__));
require $projectRoot . '/vendor/autoload.php';
$app = require_once $projectRoot . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=utf-8');

if ($path === $downPath) {
    // Mode DOWN
    $isAlreadyDown = file_exists($projectRoot . '/storage/framework/down');
    if ($isAlreadyDown) {
        renderMaintenanceGateStatus(
            'already_down',
            'Sistem Sudah Dalam Keadaan DOWN',
            'Saat ini sistem SiMonitorGuru sudah dalam keadaan NONAKTIF (DOWN). Halaman pemeliharaan 503 sedang aktif bagi seluruh pengunjung.'
        );
        exit;
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('down', [
            '--render' => 'errors.503',
        ]);

        renderMaintenanceGateStatus(
            'down_success',
            'Sistem Berhasil Dinonaktifkan (DOWN)',
            'Mode pemeliharaan telah BERHASIL DIAKTIFKAN. Seluruh akses pengguna kini ditutup dan dialihkan ke layar pemeliharaan 503.'
        );
    } catch (\Throwable $e) {
        renderMaintenanceGateStatus(
            'error',
            'Gagal Mengaktifkan Mode DOWN',
            'Terjadi kesalahan saat mematikan sistem: ' . $e->getMessage()
        );
    }
    exit;
}

if ($path === $upPath) {
    // Mode UP
    $isAlreadyDown = file_exists($projectRoot . '/storage/framework/down');
    if (!$isAlreadyDown) {
        renderMaintenanceGateStatus(
            'already_up',
            'Sistem Sudah Berjalan Normal (ONLINE)',
            'Sistem saat ini sudah dalam keadaan AKTIF / NORMAL (ONLINE). Seluruh pengguna dapat mengakses sistem seperti biasa.'
        );
        exit;
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('up');

        renderMaintenanceGateStatus(
            'up_success',
            'Sistem Berhasil Diaktifkan Kembali (UP)',
            'Mode DOWN telah dihentikan. Sistem SiMonitorGuru kini kembali ONLINE dan dapat diakses secara normal oleh seluruh pengguna.'
        );
    } catch (\Throwable $e) {
        renderMaintenanceGateStatus(
            'error',
            'Gagal Mengaktifkan Sistem',
            'Terjadi kesalahan saat menghidupkan sistem: ' . $e->getMessage()
        );
    }
    exit;
}

/**
 * Render halaman status mandiri tanpa tombol aksi
 */
function renderMaintenanceGateStatus(string $type, string $title, string $message): void
{
    $isDown = ($type === 'down_success' || $type === 'already_down');
    $badgeColor = $isDown ? '#f87171' : '#34d399';
    $badgeBg = $isDown ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)';
    $badgeBorder = $isDown ? '#ef4444' : '#10b981';
    $badgeText = $isDown ? 'SISTEM OFFLINE (DOWN)' : 'SISTEM ONLINE (UP)';
    $icon = $isDown ? '🛑' : '✅';

    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - SiMonitorGuru</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            max-width: 520px;
            width: 100%;
            padding: 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: {$badgeBg};
            color: {$badgeColor};
            border: 1px solid {$badgeBorder};
            margin-bottom: 20px;
        }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 21px; font-weight: 800; margin-bottom: 12px; color: #ffffff; line-height: 1.3; }
        p { font-size: 14px; line-height: 1.6; color: #94a3b8; margin-bottom: 8px; }
        .meta {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #334155;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">{$badgeText}</div>
        <div class="icon">{$icon}</div>
        <h1>{$title}</h1>
        <p>{$message}</p>
        <div class="meta">
            SiMonitorGuru Maintenance Gate &bull; Kontrol Murni Jalur Browser
        </div>
    </div>
</body>
</html>
HTML;
}

