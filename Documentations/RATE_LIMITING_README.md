# Rate Limiting and Throttling Implementation

This document describes the rate limiting and throttling features implemented in the application to prevent brute force attacks, DDoS attacks, and API abuse.

## Overview

The application now includes comprehensive rate limiting at multiple levels:

1. **Global Rate Limiting** - Applied to all requests (DDoS protection)
2. **Login Rate Limiting** - Per-IP throttling for login attempts
3. **API Rate Limiting** - Different limits for authenticated vs unauthenticated API requests
4. **Configurable Rate Limits** - For registration, password reset, OTP, search, and uploads

## Configuration

Rate limits are configured in `config/rate-limiting.php`. You can override these values using environment variables:

```env
# Login Rate Limiting
RATE_LIMIT_LOGIN_ATTEMPTS=5
RATE_LIMIT_LOGIN_DECAY=15

# Password Reset Rate Limiting
RATE_LIMIT_PASSWORD_RESET_ATTEMPTS=3
RATE_LIMIT_PASSWORD_RESET_DECAY=15

# OTP Rate Limiting
RATE_LIMIT_OTP_ATTEMPTS=5
RATE_LIMIT_OTP_DECAY=5

# API Rate Limiting (Authenticated)
RATE_LIMIT_API_AUTH_ATTEMPTS=60
RATE_LIMIT_API_AUTH_DECAY=1

# API Rate Limiting (Unauthenticated)
RATE_LIMIT_API_UNAUTH_ATTEMPTS=20
RATE_LIMIT_API_UNAUTH_DECAY=1

# Global Rate Limiting
RATE_LIMIT_GLOBAL_ATTEMPTS=200
RATE_LIMIT_GLOBAL_DECAY=1

# Registration Rate Limiting
RATE_LIMIT_REGISTRATION_ATTEMPTS=3
RATE_LIMIT_REGISTRATION_DECAY=60

# Search Rate Limiting
RATE_LIMIT_SEARCH_ATTEMPTS=30
RATE_LIMIT_SEARCH_DECAY=1

# Upload Rate Limiting
RATE_LIMIT_UPLOAD_ATTEMPTS=10
RATE_LIMIT_UPLOAD_DECAY=1
```

## Middleware

### 1. ThrottleGlobalRequests
Applied globally to all requests to prevent DDoS attacks.

**Default Limits:**
- 200 requests per minute per IP

**Applied:** Automatically to all routes via `bootstrap/app.php`

### 2. ThrottleLoginAttempts
Applied specifically to login routes for brute force protection.

**Default Limits:**
- 5 attempts per 15 minutes per IP

**Applied:** To `/login` POST route

### 3. ThrottleApiRequests
Applied to API endpoints with different limits for authenticated vs unauthenticated users.

**Default Limits:**
- Authenticated: 60 requests per minute per user/IP
- Unauthenticated: 20 requests per minute per IP

**Applied:** To all `/api/*` routes

### 4. ThrottleByConfig
Generic middleware that uses configuration values for flexible rate limiting.

**Usage:** `middleware('throttle:config_key')`

**Available Config Keys:**
- `registration` - User registration
- `password_reset` - Password reset requests
- `otp` - OTP generation and verification
- `search` - Search endpoints
- `upload` - File upload endpoints

## Rate Limit Headers

All rate-limited responses include the following headers:

- `X-RateLimit-Limit` - Maximum number of requests allowed
- `X-RateLimit-Remaining` - Number of requests remaining in the current window
- `Retry-After` - Seconds to wait before retrying (when limit exceeded)

## Error Responses

### HTTP 429 Too Many Requests

When a rate limit is exceeded, the application returns:

**JSON Response (for API/AJAX requests):**
```json
{
    "success": false,
    "message": "Too many requests. Please try again in X minute(s).",
    "retry_after": 900
}
```

**HTML Response (for regular requests):**
- Redirects back with error message
- Error displayed in form validation errors

## Implementation Details

### Rate Limiting Storage

Rate limits are stored in Laravel's cache system (configured in `config/cache.php`). The default cache driver is used.

### Key Generation

Rate limit keys are generated based on:
- IP address (for IP-based limiting)
- User ID + IP address (for authenticated user limiting)
- Route/endpoint identifier
- Configuration key (for config-based limiting)

### Logging

All rate limit violations are logged with:
- IP address
- User agent
- URL
- User ID (if authenticated)
- Retry after time

Logs are written to Laravel's log file with `WARNING` level.

## Security Benefits

1. **Brute Force Protection** - Prevents automated login attempts
2. **DDoS Mitigation** - Global rate limiting prevents overwhelming the server
3. **API Abuse Prevention** - Limits API usage to prevent resource exhaustion
4. **Account Security** - Multiple layers of protection (account lockout + IP throttling)
5. **Resource Protection** - Prevents abuse of expensive operations (search, uploads)

## Testing Rate Limits

To test rate limiting:

1. **Login Rate Limiting:**
   ```bash
   # Make 6 login attempts from the same IP
   for i in {1..6}; do curl -X POST http://localhost/login -d "phone=123&password=test"; done
   ```

2. **API Rate Limiting:**
   ```bash
   # Make 21 API requests without authentication
   for i in {1..21}; do curl http://localhost/api/exchange-rates/rate; done
   ```

3. **Global Rate Limiting:**
   ```bash
   # Make 201 requests to any endpoint
   for i in {1..201}; do curl http://localhost/; done
   ```

## Monitoring

Monitor rate limit violations by checking Laravel logs:

```bash
tail -f storage/logs/laravel.log | grep "Rate limit exceeded"
```

Or search for specific patterns:
```bash
grep "Rate limit exceeded" storage/logs/laravel.log
```

## Best Practices

1. **Adjust Limits Based on Usage** - Monitor your application and adjust limits as needed
2. **Use Environment Variables** - Override defaults in `.env` for different environments
3. **Monitor Logs** - Regularly check for rate limit violations to identify potential attacks
4. **Consider Whitelisting** - For trusted IPs, you may want to bypass rate limiting (future enhancement)
5. **Cache Configuration** - Ensure your cache driver is properly configured and persistent

## Future Enhancements

Potential improvements:
- IP whitelisting/blacklisting
- Geographic-based rate limiting
- Rate limit bypass for trusted users
- Rate limit dashboard/analytics
- Dynamic rate limit adjustment based on server load
- Rate limit notifications/alerts

