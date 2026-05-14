# HTTPS Enforcement Implementation

This document describes the HTTPS enforcement security features implemented in the SmartAccounting application.

## Overview

The HTTPS enforcement system provides comprehensive protection against man-in-the-middle attacks by:
1. Forcing HTTPS redirects
2. Implementing HSTS (HTTP Strict Transport Security)
3. Enforcing secure cookies
4. Supporting proxy/load balancer configurations

## Components

### 1. TrustProxies Middleware
**File:** `app/Http/Middleware/TrustProxies.php`

Handles trusted proxy configuration for applications behind load balancers, reverse proxies, or CDNs (e.g., AWS ELB, Cloudflare, Nginx).

**Configuration:**
- Set `TRUSTED_PROXIES` in `.env`:
  - `*` - Trust all proxies (recommended for load balancers)
  - Comma-separated IP addresses - Trust specific proxy IPs

**Headers Supported:**
- `X-Forwarded-For`
- `X-Forwarded-Host`
- `X-Forwarded-Port`
- `X-Forwarded-Proto`
- `X-Forwarded-AWS-ELB`

### 2. ForceHttps Middleware
**File:** `app/Http/Middleware/ForceHttps.php`

Automatically redirects HTTP requests to HTTPS.

**Features:**
- Configurable via system settings (`security_force_https`)
- Environment variable override (`FORCE_HTTPS`)
- Skips enforcement in local/testing environments by default
- Returns error for non-GET requests instead of redirecting

**Configuration:**
- Enable via System Settings → Security Settings → "Force HTTPS Redirect"
- Or set `FORCE_HTTPS=true` in `.env`
- For local development, set `FORCE_HTTPS_IN_LOCAL=true` to enable

### 3. SecurityHeaders Middleware
**File:** `app/Http/Middleware/SecurityHeaders.php`

Adds security headers to all HTTP responses.

**Headers Added:**
- **Strict-Transport-Security (HSTS):** Forces browsers to use HTTPS
  - Configurable max-age (default: 1 year)
  - Optional includeSubDomains
  - Optional preload directive
- **X-Content-Type-Options:** Prevents MIME type sniffing
- **X-Frame-Options:** Prevents clickjacking (set to SAMEORIGIN)
- **X-XSS-Protection:** Enables XSS filtering
- **Content-Security-Policy:** Controls resource loading (configurable)
- **Referrer-Policy:** Controls referrer information (configurable)
- **Permissions-Policy:** Controls browser features (configurable)

### 4. Secure Cookie Enforcement
**File:** `app/Http/Middleware/ApplySystemSettings.php`

Automatically enforces secure cookies when HTTPS is enabled.

**Behavior:**
- Enables `SESSION_SECURE_COOKIE` when HTTPS is forced
- Respects `security_enforce_secure_cookies` system setting
- Disabled in local/testing environments unless explicitly enabled

## System Settings

All HTTPS enforcement settings are available in **System Settings → Security Settings**:

1. **Force HTTPS Redirect** (`security_force_https`)
   - Type: Boolean
   - Default: `false`
   - Description: Redirect all HTTP requests to HTTPS

2. **Enable HSTS** (`security_enable_hsts`)
   - Type: Boolean
   - Default: `true`
   - Description: Enable Strict-Transport-Security header

3. **HSTS Max Age** (`security_hsts_max_age`)
   - Type: Integer (seconds)
   - Default: `31536000` (1 year)
   - Description: How long browsers should remember to use HTTPS

4. **HSTS Include Subdomains** (`security_hsts_include_subdomains`)
   - Type: Boolean
   - Default: `true`
   - Description: Apply HSTS to all subdomains

5. **HSTS Preload** (`security_hsts_preload`)
   - Type: Boolean
   - Default: `false`
   - Description: Enable HSTS preload (only if domain is submitted to HSTS preload list)

6. **Enforce Secure Cookies** (`security_enforce_secure_cookies`)
   - Type: Boolean
   - Default: `true`
   - Description: Only send cookies over HTTPS connections

7. **Content Security Policy** (`security_content_security_policy`)
   - Type: String
   - Default: `"default"` (secure nonce-based CSP, no `unsafe-inline`/`unsafe-eval`)
   - Description: Use `"default"` for the secure policy (recommended), `"disabled"` to disable, or a custom CSP string

8. **Referrer Policy** (`security_referrer_policy`)
   - Type: String
   - Default: `"strict-origin-when-cross-origin"`
   - Description: Control how much referrer information is sent

9. **Permissions Policy** (`security_permissions_policy`)
   - Type: String
   - Default: `"geolocation=(), microphone=(), camera=()"`
   - Description: Control browser features and APIs (set to "disabled" to disable)

## Environment Variables

You can override system settings using environment variables:

```env
# HTTPS Enforcement
FORCE_HTTPS=true
FORCE_HTTPS_IN_LOCAL=false
TRUSTED_PROXIES=*

# HSTS Configuration
SECURITY_ENABLE_HSTS=true
SECURITY_HSTS_MAX_AGE=31536000
SECURITY_HSTS_INCLUDE_SUBDOMAINS=true
SECURITY_HSTS_PRELOAD=false

# Secure Cookies
SESSION_SECURE_COOKIE=true

# Security Headers (use "default" for nonce-based CSP, or "disabled" to disable)
SECURITY_CSP="default"
SECURITY_REFERRER_POLICY=strict-origin-when-cross-origin
SECURITY_PERMISSIONS_POLICY="geolocation=(), microphone=(), camera=()"
```

## Middleware Registration

The middleware is registered in `bootstrap/app.php`:

1. **TrustProxies** - Registered first using Laravel's built-in `trustProxies()` method
2. **CspNonce** - Prepended to generate a CSP nonce per request (shared with views for inline scripts)
3. **ForceHttps** - Prepended early in the middleware stack
4. **SecurityHeaders** - Appended to add headers to all responses (CSP uses nonce when set to "default")
5. **ApplySystemSettings** - Applies secure cookie configuration

## Testing

### Local Development
By default, HTTPS enforcement is disabled in local/testing environments. To test:

1. Set `FORCE_HTTPS_IN_LOCAL=true` in `.env`
2. Or enable "Force HTTPS Redirect" in System Settings
3. Ensure your local server supports HTTPS (e.g., using Laravel Valet or ngrok)

### Production
1. Ensure SSL certificate is properly configured
2. Enable "Force HTTPS Redirect" in System Settings
3. Verify HSTS header is present: `curl -I https://yourdomain.com | grep Strict-Transport-Security`
4. Test secure cookies: Check browser DevTools → Application → Cookies → Secure flag

## Security Impact

**Before Implementation:**
- ❌ No HTTPS enforcement
- ❌ No HSTS headers
- ❌ Cookies could be sent over HTTP
- ❌ No proxy support for load balancers

**After Implementation:**
- ✅ Automatic HTTPS redirects
- ✅ HSTS headers prevent MITM attacks
- ✅ Secure cookies only sent over HTTPS
- ✅ Proper proxy/load balancer support
- ✅ Additional security headers (CSP, X-Frame-Options, etc.)

## Migration

Run the migration to add settings to existing databases:

```bash
php artisan migrate
```

This will automatically add all HTTPS enforcement settings to the `system_settings` table.

## Troubleshooting

### Issue: HTTPS redirect loop
**Solution:** Ensure `TRUSTED_PROXIES` is set correctly if behind a proxy/load balancer.

### Issue: Cookies not working after enabling HTTPS
**Solution:** Check that `SESSION_SECURE_COOKIE` is set correctly and that your SSL certificate is valid.

### Issue: HSTS header not appearing
**Solution:** 
1. Ensure "Enable HSTS" is enabled in System Settings
2. Verify the request is actually HTTPS (check `$request->secure()`)
3. Check middleware order in `bootstrap/app.php`

### Issue: Can't access site in local development
**Solution:** HTTPS enforcement is disabled by default in local/testing. If you enabled it, either:
- Disable it in System Settings
- Set `FORCE_HTTPS_IN_LOCAL=false` in `.env`
- Set up local HTTPS (Laravel Valet, ngrok, etc.)

## Login and Brute-Force Protection

The login interface is protected against brute force and credential stuffing:

- **Per-account rate limit:** Maximum failed attempts per phone number (default 5 per 15 minutes). Account lockout is also enforced via `LoginAttempt` (configurable in System Settings: Login Attempts Limit, Lockout Duration).
- **Per-IP rate limit:** Maximum total login attempts per IP across all usernames (default 20 per 15 minutes). This limits abuse of leaked credential lists from a single IP.
- **Activity logging:** Failed and successful logins are logged for audit.
- **Retry-After header:** Sent when rate limited so clients know when to retry.

**Recommendation (e.g. from security scans):** Run occasional password audits (e.g. check for weak or compromised passwords) and ensure the login interface cannot be bypassed with common or leaked credentials. The above limits reduce the impact of credential stuffing; strong password policy and optional 2FA are configured in System Settings.

## References

- [Laravel Trust Proxies](https://laravel.com/docs/11.x/requests#configuring-trusted-proxies)
- [HSTS Preload List](https://hstspreload.org/)
- [OWASP Secure Headers](https://owasp.org/www-project-secure-headers/)
- [Mozilla Security Guidelines](https://infosec.mozilla.org/guidelines/web_security)

