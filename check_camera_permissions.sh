#!/bin/bash

echo "=========================================="
echo "Camera QR Scanner - Permissions Check"
echo "=========================================="
echo ""

# 1. Check database setting
echo "1. Checking database setting..."
DB_VALUE=$(php artisan tinker --execute="echo DB::table('system_settings')->where('key', 'security_permissions_policy')->value('value');" 2>/dev/null | tail -1)
echo "   Database value: $DB_VALUE"
echo ""

# 2. Check if setting is correct
if [[ "$DB_VALUE" == *"camera=(self)"* ]]; then
    echo "   ✓ Database setting is correct"
else
    echo "   ✗ Database setting needs update!"
    echo "   Run: php artisan tinker --execute=\"DB::table('system_settings')->where('key', 'security_permissions_policy')->update(['value' => 'geolocation=(), microphone=(), camera=(self)']);\""
fi
echo ""

# 3. Check environment variable
echo "2. Checking environment variable..."
if grep -q "SECURITY_PERMISSIONS_POLICY" .env 2>/dev/null; then
    ENV_VALUE=$(grep "SECURITY_PERMISSIONS_POLICY" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    echo "   Found in .env: $ENV_VALUE"
    if [[ "$ENV_VALUE" == *"camera=(self)"* ]]; then
        echo "   ✓ Environment variable is correct"
    else
        echo "   ⚠ Environment variable may override database setting"
    fi
else
    echo "   ✓ No override in .env (using database setting)"
fi
echo ""

# 4. Test header (requires actual domain)
echo "3. Testing Permissions-Policy header..."
echo "   Note: Replace 'yourdomain.com' with your actual domain"
echo ""
echo "   Run this command with your actual domain:"
echo "   curl -I https://YOUR-ACTUAL-DOMAIN.com/sales/pos 2>&1 | grep -i 'permissions-policy'"
echo ""
echo "   Or test any page:"
echo "   curl -I https://YOUR-ACTUAL-DOMAIN.com 2>&1 | grep -i 'permissions-policy'"
echo ""

# 5. Check middleware registration
echo "4. Checking middleware registration..."
if grep -q "SecurityHeaders" bootstrap/app.php 2>/dev/null; then
    echo "   ✓ SecurityHeaders middleware is registered"
else
    echo "   ✗ SecurityHeaders middleware not found in bootstrap/app.php"
fi
echo ""

# 6. Clear caches reminder
echo "5. Cache status..."
echo "   If you just updated the database, run:"
echo "   php artisan config:clear && php artisan cache:clear && php artisan view:clear"
echo ""

echo "=========================================="
echo "Next Steps:"
echo "=========================================="
echo "1. Update database (if needed):"
echo "   php artisan tinker --execute=\"DB::table('system_settings')->where('key', 'security_permissions_policy')->update(['value' => 'geolocation=(), microphone=(), camera=(self)']);\""
echo ""
echo "2. Clear caches:"
echo "   php artisan config:clear && php artisan cache:clear && php artisan view:clear"
echo ""
echo "3. Test with your actual domain:"
echo "   curl -I https://YOUR-DOMAIN.com 2>&1 | grep -i 'permissions-policy'"
echo ""
echo "4. Restart web server:"
echo "   sudo systemctl restart apache2  # or nginx"
echo ""

