@echo off
cd /d "%~dp0\.."
:loop
php artisan mqtt:subscribe
echo Subscriber berhenti, memulai ulang dalam 3 detik...
timeout /t 3 /nobreak >nul
goto loop
