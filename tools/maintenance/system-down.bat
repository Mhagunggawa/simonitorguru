@echo off
title SiMonitorGuru - Matikan Sistem (Mode DOWN)
color 0C
cls

:: Beralih ke root directory project (2 level di atas tools\maintenance)
cd /d "%~dp0..\.."

echo ========================================================
echo         SIMONITORGURU - MODE PEMELIHARAAN (DOWN)
echo ========================================================
echo.
echo Sedang menonaktifkan sistem...
echo Direktori: %CD%
echo.
php artisan down --render="errors.503"
echo.
echo ========================================================
echo [STATUS] SISTEM SEKARANG DALAM KEADAAN DOWN / OFFLINE!
echo Akses pengguna telah ditutup dan dialihkan ke layar pemeliharaan.
echo.
echo Untuk menghidupkan kembali sistem:
echo - Jalankan file: tools\maintenance\system-up.bat
echo ========================================================
echo.
pause

