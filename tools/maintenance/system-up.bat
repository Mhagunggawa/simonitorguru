@echo off
title SiMonitorGuru - Hidupkan Sistem (Mode UP / Online)
color 0A
cls

:: Beralih ke root directory project (2 level di atas tools\maintenance)
cd /d "%~dp0..\.."

echo ========================================================
echo         SIMONITORGURU - MENGHIDUPKAN SISTEM (UP)
echo ========================================================
echo.
echo Sedang mengaktifkan kembali sistem...
echo Direktori: %CD%
echo.
php artisan up
echo.
echo ========================================================
echo [STATUS] SISTEM SEKARANG KEMBALI NORMAL / ONLINE!
echo Seluruh pengguna kini dapat mengakses sistem seperti biasa.
echo ========================================================
echo.
pause

