@echo off
echo Reminder Service Started at %time% on %date%
echo Press Ctrl+C to stop the service

:loop
echo Running reminders at %time%...
cd /d "C:\Users\ercma\Documents\3-kursa-projekts\backend"
php artisan app:send-reminders
echo Waiting 60 seconds...
timeout /t 60 /nobreak
goto loop

