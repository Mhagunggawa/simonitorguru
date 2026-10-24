<?php

namespace App\Services;

use App\Data\BackupArchive;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\DbDumper\Databases\MySql;
use Throwable;
use ZipArchive;

class BackupService
{
    public function create(?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null, bool $allMedia = true): BackupArchive
    {
        $this->ensureSupportedEnvironment();

        $timestamp = now()->format('Y_m_d_His');
        $identifier = Str::lower(Str::random(6));
        $workingDirectory = Storage::disk('local')->path("backups/tmp_{$timestamp}_{$identifier}");
        $sqlPath = $workingDirectory.'/database.sql';

        if ($allMedia || ! $startDate || ! $endDate) {
            $archiveFilename = "backup_penuh_simonitorguru_{$timestamp}_{$identifier}.zip";
        } else {
            $archiveFilename = "backup_{$startDate->format('Y-m-d')}_sd_{$endDate->format('Y-m-d')}_{$identifier}.zip";
        }

        $archivePath = Storage::disk('local')->path("backups/{$archiveFilename}");

        File::ensureDirectoryExists($workingDirectory);

        try {
            $this->dumpDatabase($sqlPath);
            $this->buildArchive($archivePath, $sqlPath, $startDate, $endDate, $allMedia);

            return new BackupArchive($archivePath, $archiveFilename);
        } catch (Throwable $exception) {
            File::delete($archivePath);

            throw $exception;
        } finally {
            File::deleteDirectory($workingDirectory);
        }
    }

    private function ensureSupportedEnvironment(): void
    {
        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('Fitur backup hanya mendukung koneksi MySQL.');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP ZIP belum tersedia.');
        }
    }

    private function dumpDatabase(string $destination): void
    {
        $connection = config('database.connections.mysql');

        $dumper = MySql::create()
            ->setDbName((string) $connection['database'])
            ->setUserName((string) $connection['username'])
            ->setPassword((string) ($connection['password'] ?? ''))
            ->setHost((string) $connection['host'])
            ->setPort((int) $connection['port'])
            ->setDefaultCharacterSet((string) $connection['charset'])
            ->doNotSkipComments()
            ->useSingleTransaction()
            ->useQuick()
            ->includeRoutines();

        if (! empty($connection['unix_socket'])) {
            $dumper->setSocket($connection['unix_socket']);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $this->configureWindowsEnvironment();
            $dumper->doNotUseColumnStatistics();
        }

        $binaryPath = $this->dumpBinaryPath();
        if ($binaryPath !== null) {
            $dumper->setDumpBinaryPath($binaryPath);
        }

        $dumper->dumpToFile($destination);
    }

    private function buildArchive(
        string $archivePath,
        string $sqlPath,
        ?CarbonInterface $startDate,
        ?CarbonInterface $endDate,
        bool $allMedia = true,
    ): void {
        $zip = new ZipArchive;

        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File ZIP backup tidak dapat dibuat.');
        }

        try {
            $zip->addFile($sqlPath, 'database.sql');
            $zip->addEmptyDir('dokumentasi');
            $mediaCount = $this->addDocumentation($zip, $startDate, $endDate, $allMedia);

            // Buat file metadata ringkasan backup
            $infoContent = $this->generateBackupInfo($startDate, $endDate, $allMedia, $mediaCount);
            $zip->addFromString('info_backup.txt', $infoContent);
        } finally {
            $zip->close();
        }
    }

    private function addDocumentation(
        ZipArchive $zip,
        ?CarbonInterface $startDate,
        ?CarbonInterface $endDate,
        bool $allMedia = true,
    ): int {
        $disk = Storage::disk('public');
        $addedCount = 0;

        foreach ($disk->allFiles() as $relativePath) {
            if (str_starts_with(basename($relativePath), '.')) {
                continue;
            }

            if (! $allMedia && $startDate && $endDate) {
                $lastModified = Carbon::createFromTimestamp($disk->lastModified($relativePath));
                if (! $lastModified->betweenIncluded($startDate, $endDate)) {
                    continue;
                }
            }

            $zip->addFile($disk->path($relativePath), 'dokumentasi/'.$relativePath);
            $addedCount++;
        }

        return $addedCount;
    }

    private function generateBackupInfo(
        ?CarbonInterface $startDate,
        ?CarbonInterface $endDate,
        bool $allMedia,
        int $mediaCount,
    ): string {
        $settingan = \App\Models\Settingan::first();
        $appName = $settingan?->nama_aplikasi ?? 'Simonitorguru';
        $instansi = $settingan?->nama_instansi ?? 'Sekolah';
        $now = now()->locale('id')->translatedFormat('l, d F Y H:i:s');

        $tipe = $allMedia || ! $startDate || ! $endDate
            ? 'Pencadangan Penuh (Full Backup Database & Seluruh Foto/Dokumentasi)'
            : 'Pencadangan Periode (' . $startDate->format('d/m/Y') . ' s/d ' . $endDate->format('d/m/Y') . ')';

        return "=================================================================\n"
            . "                    INFORMASI PENCADANGAN SISTEM                 \n"
            . "=================================================================\n"
            . "Nama Aplikasi      : {$appName}\n"
            . "Nama Instansi      : {$instansi}\n"
            . "Waktu Backup       : {$now}\n"
            . "Jenis Backup       : {$tipe}\n"
            . "Status Database    : Berhasil (database.sql)\n"
            . "Jumlah File Media  : {$mediaCount} berkas foto/gambar/dokumen\n"
            . "Folder Dokumentasi : /dokumentasi\n"
            . "=================================================================\n"
            . "Petunjuk Pemulihan (Restore):\n"
            . "1. Ekstrak file database.sql untuk diimpor ke MySQL/MariaDB.\n"
            . "2. Salin isi folder /dokumentasi ke direktori storage/app/public/ aplikasi.\n"
            . "=================================================================\n";
    }

    private function dumpBinaryPath(): ?string
    {
        $configuredPath = config('backup.mysql_dump_path');
        if (is_string($configuredPath) && $configuredPath !== '') {
            return rtrim($configuredPath, '\\/').'/';
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $paths = glob('C:\\laragon\\bin\\mysql\\mysql*\\bin', GLOB_ONLYDIR);

        return $paths === false || $paths === []
            ? null
            : rtrim((string) end($paths), '\\/').'/';
    }

    private function configureWindowsEnvironment(): void
    {
        $systemRoot = getenv('SYSTEMROOT') ?: 'C:\\Windows';
        putenv("SYSTEMROOT={$systemRoot}");
        $_ENV['SYSTEMROOT'] = $systemRoot;
    }
}
