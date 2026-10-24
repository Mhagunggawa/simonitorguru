<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressor
{
    /**
     * Menyimpan gambar dengan kompresi cerdas ke ukuran target ~100KB.
     * Jika file sudah <= 115KB (sudah dikompres di browser saat pengambilan foto),
     * langsung disimpan tanpa pemrosesan ulang demi performa tinggi.
     * Jika file > 115KB, akan dioptimasi menggunakan GD agar tidak membebani storage server.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param int $targetSizeKb
     * @return string Path relatif file yang disimpan
     */
    public static function storeCompressed(UploadedFile $file, string $directory, int $targetSizeKb = 100): string
    {
        $targetBytes = $targetSizeKb * 1024;
        $fileSize = $file->getSize();

        // Jika ukuran file sudah di bawah atau sekitar target (< 115 KB), simpan langsung
        if ($fileSize && $fileSize <= ($targetBytes * 1.15)) {
            return $file->store($directory, 'public');
        }

        // Jika lebih besar dan GD aktif, kompres di server sebagai pengaman ekstra
        if (extension_loaded('gd')) {
            try {
                $imagePath = $file->getRealPath();
                $imageInfo = @getimagesize($imagePath);

                if ($imageInfo) {
                    $mime = $imageInfo['mime'];
                    $src = match ($mime) {
                        'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($imagePath),
                        'image/png' => @imagecreatefrompng($imagePath),
                        'image/webp' => @imagecreatefromwebp($imagePath),
                        default => null,
                    };

                    if ($src) {
                        $width = imagesx($src);
                        $height = imagesy($src);

                        // Skalakan resolusi jika dimensi terlalu besar (max 1024px)
                        $maxDim = 1024;
                        if (max($width, $height) > $maxDim) {
                            $scale = $maxDim / max($width, $height);
                            $newWidth = (int) round($width * $scale);
                            $newHeight = (int) round($height * $scale);

                            $dst = imagecreatetruecolor($newWidth, $newHeight);
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            imagedestroy($src);
                            $src = $dst;
                        }

                        // Kompresi bertahap untuk mendekati target ~100KB
                        $tempPath = tempnam(sys_get_temp_dir(), 'img_compress_');
                        $quality = 80;
                        imagejpeg($src, $tempPath, $quality);

                        while (filesize($tempPath) > $targetBytes && $quality > 30) {
                            $quality -= 10;
                            imagejpeg($src, $tempPath, $quality);
                        }

                        imagedestroy($src);

                        $filename = Str::random(40) . '.jpg';
                        $destination = $directory . '/' . $filename;
                        Storage::disk('public')->put($destination, file_get_contents($tempPath));
                        @unlink($tempPath);

                        return $destination;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback jika terjadi kegagalan tak terduga
                report($e);
            }
        }

        return $file->store($directory, 'public');
    }
}
