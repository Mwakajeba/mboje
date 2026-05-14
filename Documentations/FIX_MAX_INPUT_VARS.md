# Fix for Purchase Invoice Update Issue - max_input_vars

## Problem Summary

The purchase invoice update functionality was losing items when updating invoices with many items (e.g., 100+ items). This was caused by PHP's `max_input_vars` configuration limit.

### Root Cause

- **Current Setting**: `max_input_vars = 1000` (default)
- **What it does**: Limits the number of input variables PHP will accept from a form submission
- **Why it's a problem**:
  - 100 items × ~15 fields per item = **1,500 input variables**
  - When the limit is exceeded, PHP **silently truncates** the `$_POST` array
  - Controller receives incomplete data (e.g., only 60-70 items instead of 100)
  - Controller deletes ALL existing items and only recreates the items it received
  - **Result**: ~30-40 items are permanently lost!

### What Was Fixed

1. **Added Safeguard in Controller** (PurchaseInvoiceController.php:963-983):
   - Detects when received items count < existing items count
   - Blocks the update to prevent data loss
   - Shows clear error message with solution

2. **Enhanced Logging**:
   - Added `max_input_vars` value to logs
   - Added POST variable count to logs
   - Helps diagnose truncation issues

3. **Error Prevention**:
   - Update is now blocked if truncation is detected
   - User sees helpful error message instead of losing data

## Solution: Increase max_input_vars

You need to increase `max_input_vars` to at least **5000** (recommended: 10000 for future growth).

### Method 1: Edit php.ini (Recommended)

1. **Find your PHP version and php.ini file**:
   ```bash
   # Check PHP version used by web server (create test file)
   echo "<?php echo phpversion(); ?>" > /var/www/html/smartaccounting/public/test_php_version.php
   # Visit: http://your-domain.com/test_php_version.php
   # Then delete: rm /var/www/html/smartaccounting/public/test_php_version.php

   # OR check which PHP-FPM is running
   ps aux | grep php-fpm

   # Find the correct php.ini location
   php --ini | grep "Loaded Configuration File"
   ```

2. **Edit the file** (replace 8.2 with your PHP version):
   ```bash
   sudo nano /etc/php/8.2/fpm/php.ini  # Adjust version as needed (8.2, 8.3, etc.)
   ```

3. **Find and update the setting** (search for `max_input_vars`):
   ```ini
   ; Change from:
   max_input_vars = 1000

   ; To:
   max_input_vars = 10000
   ```

   If the line doesn't exist, add it in the `[PHP]` section.

4. **Also update for CLI** (if you use queue workers):
   ```bash
   sudo nano /etc/php/8.2/cli/php.ini  # Use same version as FPM
   ```

   Add/update the same setting:
   ```ini
   max_input_vars = 10000
   ```

5. **Restart PHP-FPM** (use the correct version):
   ```bash
   # For PHP 8.2:
   sudo systemctl restart php8.2-fpm

   # For other versions, adjust the version number:
   # sudo systemctl restart php8.1-fpm
   # sudo systemctl restart php8.3-fpm
   ```

6. **Restart your web server** (if using Apache):
   ```bash
   sudo systemctl restart apache2
   ```

7. **Verify the change**:
   ```bash
   php -i | grep max_input_vars
   ```

   Should show:
   ```
   max_input_vars => 10000 => 10000
   ```

### Method 2: Using .htaccess (Apache Only)

If you can't edit php.ini, add this to your `.htaccess` file in the project root:

```apache
php_value max_input_vars 10000
```

**Note**: This method only works if your server allows `.htaccess` overrides.

### Method 3: Using .user.ini (Some Shared Hosting)

Create or edit `.user.ini` in your project root:

```ini
max_input_vars = 10000
```

**Note**: Changes may take 5 minutes to take effect (depends on `user_ini.cache_ttl`).

## Verification Steps

After making the change:

1. **Check current setting**:
   ```bash
   php -r "echo ini_get('max_input_vars');"
   ```

   Should output: `10000`

2. **Test with purchase invoice**:
   - Open an invoice with 100+ items
   - Add a new item
   - Click "Update Purchase Invoice"
   - Should update successfully without losing any items

3. **Check logs** (optional):
   ```bash
   tail -f storage/logs/laravel.log | grep "max_input_vars"
   ```

   You should see the new limit in the logs.

## Recommended Settings

For optimal performance with large invoices:

```ini
max_input_vars = 10000        # Number of input variables
max_input_nesting_level = 64  # Depth of nested arrays (default is usually fine)
memory_limit = 512M           # Increase if processing very large requests
post_max_size = 100M          # Maximum POST data size
upload_max_filesize = 100M    # Maximum upload size
```

## Troubleshooting

### Issue: "Update blocked: Received X items but invoice has Y items"

**Cause**: max_input_vars is still too low or changes haven't taken effect.

**Solution**:
1. Verify the setting: `php -i | grep max_input_vars`
2. Make sure you edited the correct php.ini (FPM, not CLI)
3. Restart PHP-FPM: `sudo systemctl restart php8.2-fpm`
4. Clear application cache: `php artisan cache:clear`

### Issue: Changes not taking effect

**Possible causes**:
- Edited wrong php.ini file (there are separate files for CLI and FPM)
- Forgot to restart PHP-FPM
- Server uses Apache mod_php (not FPM)
- Hosting provider restrictions

**Solution**:
1. Check which PHP SAPI is used:
   ```bash
   php -r "echo php_sapi_name();"
   ```

2. If output is `apache2handler`, you need to restart Apache instead:
   ```bash
   sudo systemctl restart apache2
   ```

3. If on shared hosting, contact your hosting provider.

### Issue: Still losing items after fix

**Possible causes**:
- Browser/proxy timeout (for very large forms)
- Network issues during submission
- Database connection timeout

**Solutions**:
1. Use the async job path (already implemented for 50+ items)
2. Increase PHP timeout settings:
   ```ini
   max_execution_time = 300
   max_input_time = 300
   ```

3. Adjust job threshold in `config/queue.php`:
   ```php
   'purchase_invoice_job_threshold' => 30,  // Use jobs for 30+ items instead of 50+
   ```

## For System Administrators

If you manage this server, consider:

1. **Set reasonable defaults** for all projects:
   - `max_input_vars = 10000` (instead of 1000)
   - `memory_limit = 512M` (instead of 128M)
   - `post_max_size = 100M` (instead of 8M)

2. **Monitor logs** for truncation issues:
   ```bash
   grep "max_input_vars truncation" storage/logs/laravel.log
   ```

3. **Add to server monitoring**:
   - Alert when POST requests exceed 80% of max_input_vars
   - Monitor form submission failures

## Additional Information

### Why not use AJAX/JSON instead of form submission?

- **Current implementation** uses traditional form submission
- Changing to AJAX would require:
  - Significant frontend refactoring
  - JSON serialization (which can also hit size limits)
  - More complex error handling
- **Recommended**: Fix max_input_vars first (quick), then consider AJAX later

### Why not split into multiple requests?

- **Complexity**: Would require JavaScript to chunk items
- **Atomicity**: All items must be saved together (transactional)
- **User experience**: Single submit button is simpler
- **Recommended**: The job-based approach (already implemented for 50+ items) handles this well

### Performance Impact of Increasing max_input_vars

**Minimal**:
- Only affects POST/GET parsing, not runtime performance
- Slightly more memory used for very large forms
- Negligible impact on normal requests

**Benefits**:
- Prevents data loss
- Better user experience
- Supports business growth (more items per invoice)

## Contact

If you continue to experience issues after following these steps, please provide:
1. Output of `php -i | grep max_input_vars`
2. Output of `php -r "echo php_sapi_name();"`
3. Laravel log excerpts showing the error
4. Number of items in the invoice being updated

---

**Last Updated**: 2026-01-12
**Applied Fix**:
1. Added truncation detection in PurchaseInvoiceController (lines 963-983)
2. Updated PHP configuration on demo.smartsoft.co.tz (PHP 8.2):
   - Changed `max_input_vars` from 1000 to 10000 in `/etc/php/8.2/fpm/php.ini`
   - Changed `max_input_vars` from 1000 to 10000 in `/etc/php/8.2/cli/php.ini`
   - Restarted php8.2-fpm service
   - Verified via phpinfo: max_input_vars now shows 10000

**Status**: Issue fully resolved on demo server. Other servers need PHP configuration update.
