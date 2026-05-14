@echo off
echo Starting Laravel Queue Worker...
echo This will process LIPISHA customer creation jobs continuously
echo Press Ctrl+C to stop
echo.
php artisan queue:work --tries=15 --timeout=180 --max-jobs=1000 --max-time=3600
pause

