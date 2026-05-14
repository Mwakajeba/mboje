# Deploying Camera QR Scanner to Live Server

This guide explains how to deploy the camera QR scanner functionality to your live/production server.

## Quick Commands (Run These on Your Server)

### Step 1: Check Current Database Setting

```bash
php artisan tinker --execute="echo 'Current value: ' . DB::table('system_settings')->where('key', 'security_permissions_policy')->value('value') . PHP_EOL;"
```

### Step 2: Update Database Setting

```bash
php artisan tinker --execute="DB::table('system_settings')->where('key', 'security_permissions_policy')->update(['value' => 'geolocation=(), microphone=(), camera=(self)']); echo 'Updated successfully!' . PHP_EOL;"
```

### Step 3: Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 4: Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5: Restart Web Server

**For Apache:**
```bash
sudo systemctl restart apache2
```

**For Nginx:**
```bash
sudo systemctl restart nginx
```

**For PHP-FPM:**
```bash
sudo systemctl restart php8.1-fpm
# or your PHP version (php8.2-fpm, php8.3-fpm, etc.)
```

### Step 6: Verify Header (Replace YOUR-DOMAIN with your actual domain)

```bash
curl -I https://YOUR-DOMAIN.com/sales/pos 2>&1 | grep -i "permissions-policy"
```

**OR test any page:**
```bash
curl -I https://YOUR-DOMAIN.com 2>&1 | grep -i "permissions-policy"
```

You should see:
```
Permissions-Policy: geolocation=(), microphone=(), camera=(self)
```

## Complete One-Line Command

Run this single command to do everything:

```bash
php artisan tinker --execute="DB::table('system_settings')->where('key', 'security_permissions_policy')->update(['value' => 'geolocation=(), microphone=(), camera=(self)']);" && php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan config:cache && echo "Done! Now restart your web server."
```

## Important Requirements

1. **HTTPS Must Be Enabled**: Camera requires secure context
   - Ensure SSL certificate is valid
   - Check `.env`: `APP_URL=https://yourdomain.com`
   - Enable HTTPS redirect in System Settings

2. **Browser Permissions**: Users must grant camera permission when prompted

## Troubleshooting

### No Header in Response

If `curl` shows no Permissions-Policy header:

1. **Check database value:**
   ```bash
   php artisan tinker --execute="echo DB::table('system_settings')->where('key', 'security_permissions_policy')->value('value');"
   ```

2. **Check if middleware is registered:**
   ```bash
   grep -n "SecurityHeaders" bootstrap/app.php
   ```
   Should show line with `SecurityHeaders::class`

3. **Check environment variable override:**
   ```bash
   grep SECURITY_PERMISSIONS_POLICY .env
   ```
   If found, it may override database setting

4. **Clear all caches again:**
   ```bash
   php artisan config:clear && php artisan cache:clear && php artisan view:clear
   ```

### Header Shows `camera=()` Instead of `camera=(self)`

The database wasn't updated. Run Step 2 again.

### Camera Still Not Working

1. Verify HTTPS is working (check browser shows padlock)
2. Check browser console for errors (F12 → Console)
3. Verify browser permissions (click lock icon → Site settings → Camera)
4. Test in incognito/private window

## Testing Checklist

- [ ] Database setting updated to `camera=(self)`
- [ ] All caches cleared
- [ ] Production caches regenerated
- [ ] Web server restarted
- [ ] HTTPS is enabled and working
- [ ] Permissions-Policy header shows `camera=(self)`
- [ ] Browser permissions granted
- [ ] QR scanner opens camera successfully

## Notes

- **localhost** is considered secure (works with HTTP)
- **127.0.0.1** is NOT secure (requires HTTPS)
- Production servers MUST use HTTPS
- Some browsers cache permissions policy aggressively - may need hard refresh
