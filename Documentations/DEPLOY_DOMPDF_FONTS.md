# Deploying DomPDF Fonts to Live Server

This guide explains how to set up DomPDF fonts on your live/production server.

## Quick Setup (Run These Commands on Your Live Server)

### Step 1: Navigate to Your Project Directory

```bash
cd /var/www/html/smartaccounting
# or wherever your project is located
```

### Step 2: Pull Latest Code (if using Git)

```bash
git pull origin main
# or your branch name
```

### Step 3: Install/Update Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### Step 4: Run the Font Loading Command

```bash
php artisan dompdf:load-fonts
```

This command will:
- Create the `storage/fonts` directory if it doesn't exist
- Copy all DejaVu Sans fonts from vendor to storage/fonts
- Verify fonts are properly installed

### Step 5: Set Proper Permissions

```bash
# Ensure storage/fonts directory is writable
chmod -R 755 storage/fonts
chown -R www-data:www-data storage/fonts
# or your web server user (nginx, apache, etc.)
```

### Step 6: Clear and Cache Configuration

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
```

### Step 7: Verify Fonts Are Installed

```bash
ls -la storage/fonts/DejaVuSans*
```

You should see at least these files:
- `DejaVuSans.ttf`
- `DejaVuSans.ufm`
- `DejaVuSans-Bold.ttf`
- `DejaVuSans-Bold.ufm`
- `DejaVuSans-Oblique.ttf`
- `DejaVuSans-Oblique.ufm`
- `DejaVuSans-BoldOblique.ttf`
- `DejaVuSans-BoldOblique.ufm`

## Complete One-Line Command

Run this single command to do everything:

```bash
cd /var/www/html/smartaccounting && php artisan dompdf:load-fonts && chmod -R 755 storage/fonts && php artisan config:clear && php artisan cache:clear && php artisan config:cache && echo "✅ DomPDF fonts installed successfully!"
```

## Verification

After setup, test the PDF export:

1. Go to: `Inventory → Reports → Inventory Quantity Summary`
2. Click "Export PDF"
3. The PDF should generate without font errors

## Troubleshooting

### If fonts are not found:

1. **Check directory exists:**
   ```bash
   ls -la storage/fonts/
   ```

2. **Check permissions:**
   ```bash
   ls -la storage/ | grep fonts
   ```
   Should show `drwxr-xr-x` or `drwxrwxrwx`

3. **Manually copy fonts if needed:**
   ```bash
   mkdir -p storage/fonts
   cp vendor/dompdf/dompdf/lib/fonts/DejaVuSans*.ttf storage/fonts/
   cp vendor/dompdf/dompdf/lib/fonts/DejaVuSans*.ufm storage/fonts/
   chmod 644 storage/fonts/*
   ```

4. **Check config file:**
   ```bash
   cat config/dompdf.php | grep default_font
   ```
   Should show: `'default_font' => 'dejavu sans',`

### If still getting font errors:

1. **Check web server user:**
   ```bash
   ps aux | grep -E 'nginx|apache|php-fpm' | head -1
   ```

2. **Set ownership to web server user:**
   ```bash
   # For Apache/Nginx with PHP-FPM
   chown -R www-data:www-data storage/fonts
   
   # Or for your specific user
   chown -R your-user:your-group storage/fonts
   ```

3. **Ensure directory is writable:**
   ```bash
   chmod -R 755 storage/fonts
   ```

## Adding to Deployment Script

If you have a deployment script, add this line:

```bash
php artisan dompdf:load-fonts
```

## Important Notes

- ✅ Fonts are copied from `vendor/dompdf/dompdf/lib/fonts/` to `storage/fonts/`
- ✅ The command is idempotent - safe to run multiple times
- ✅ Fonts will be updated if vendor fonts are newer
- ✅ No database changes required
- ✅ Works with Laravel DomPDF package (barryvdh/laravel-dompdf)

## After Deployment

Once fonts are installed, all PDF exports will use `dejavu sans` font automatically. No further action needed unless you update DomPDF package.

