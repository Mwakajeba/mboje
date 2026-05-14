# PowerShell script to start Laravel Queue Worker
Write-Host "Starting Laravel Queue Worker..." -ForegroundColor Green
Write-Host "This will process LIPISHA customer creation jobs continuously" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

# Start queue worker with proper settings
php artisan queue:work --tries=15 --timeout=180 --max-jobs=1000 --max-time=3600

