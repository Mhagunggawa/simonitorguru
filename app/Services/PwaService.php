<?php

namespace App\Services;

use App\Models\Settingan;

class PwaService
{
    /**
     * Sinkronisasi aset PWA (icon dan manifest.json) berdasarkan settingan aplikasi di database
     */
    public static function syncPwaAssets(?Settingan $settingan = null): void
    {
        $settingan = $settingan ?? Settingan::first();
        if (!$settingan) {
            return;
        }

        $namaAplikasi = $settingan->nama_aplikasi ?: 'SIMON';
        $namaInstansi = $settingan->nama_instansi ?: 'SMKN 1 GORONTALO';

        $logoPath = $settingan->logo_aplikasi;
        $logoFullPath = $logoPath ? storage_path('app/public/' . $logoPath) : null;
        $logoUrl = $logoPath ? '/storage/' . ltrim($logoPath, '/') : '/apple-touch-icon.png';

        if ($logoFullPath && file_exists($logoFullPath)) {
            $imgInfo = @getimagesize($logoFullPath);
            if ($imgInfo) {
                $mime = $imgInfo['mime'];
                $src = null;

                switch ($mime) {
                    case 'image/png':
                        $src = @imagecreatefrompng($logoFullPath);
                        break;
                    case 'image/jpeg':
                        $src = @imagecreatefromjpeg($logoFullPath);
                        break;
                    case 'image/webp':
                        $src = @imagecreatefromwebp($logoFullPath);
                        break;
                }

                if ($src) {
                    $origW = imagesx($src);
                    $origH = imagesy($src);

                    if (!file_exists(public_path('icons'))) {
                        mkdir(public_path('icons'), 0755, true);
                    }

                    $sizes = [
                        public_path('icons/icon-192x192.png') => 192,
                        public_path('icons/icon-512x512.png') => 512,
                        public_path('apple-touch-icon.png') => 180,
                    ];

                    foreach ($sizes as $targetPath => $size) {
                        $dst = imagecreatetruecolor($size, $size);
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                        imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $origW, $origH);
                        imagepng($dst, $targetPath, 9);
                        imagedestroy($dst);
                    }

                    imagedestroy($src);
                }
            }
        }

        $icons = [
            [
                'src' => $logoUrl,
                'sizes' => '192x192 512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/icons/icon-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/icons/icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/icons/icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];

        $manifest = [
            'name' => "{$namaAplikasi} - {$namaInstansi}",
            'short_name' => $namaAplikasi,
            'description' => "Sistem Informasi Monitoring Kedisiplinan Guru {$namaInstansi}",
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#ffffff',
            'theme_color' => '#79AE6F',
            'icons' => $icons,
        ];

        file_put_contents(public_path('manifest.json'), json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
