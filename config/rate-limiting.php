<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the rate limiting configuration for different
    | parts of the application. Rate limits are defined as:
    | 'max_attempts' => number of requests allowed
    | 'decay_minutes' => time window in minutes
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Login Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for authentication endpoints. Per-identifier limits prevent
    | brute force on a single account; per-IP limits prevent one attacker from
    | trying many usernames (e.g. leaked credentials) from a single IP.
    |
    */
    'login' => [
        'max_attempts' => env('RATE_LIMIT_LOGIN_ATTEMPTS', 5),
        'decay_minutes' => env('RATE_LIMIT_LOGIN_DECAY', 15),
        'max_attempts_per_ip' => env('RATE_LIMIT_LOGIN_PER_IP_ATTEMPTS', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for password reset requests to prevent abuse.
    |
    */
    'password_reset' => [
        'max_attempts' => env('RATE_LIMIT_PASSWORD_RESET_ATTEMPTS', 3),
        'decay_minutes' => env('RATE_LIMIT_PASSWORD_RESET_DECAY', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for OTP generation and verification.
    |
    */
    'otp' => [
        'max_attempts' => env('RATE_LIMIT_OTP_ATTEMPTS', 5),
        'decay_minutes' => env('RATE_LIMIT_OTP_DECAY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for API endpoints. Applied per authenticated user or IP.
    |
    */
    'api' => [
        'authenticated' => [
            'max_attempts' => env('RATE_LIMIT_API_AUTH_ATTEMPTS', 60),
            'decay_minutes' => env('RATE_LIMIT_API_AUTH_DECAY', 1),
        ],
        'unauthenticated' => [
            'max_attempts' => env('RATE_LIMIT_API_UNAUTH_ATTEMPTS', 20),
            'decay_minutes' => env('RATE_LIMIT_API_UNAUTH_DECAY', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Global rate limits applied to all requests to prevent DDoS attacks.
    |
    */
    'global' => [
        'max_attempts' => env('RATE_LIMIT_GLOBAL_ATTEMPTS', 200),
        'decay_minutes' => env('RATE_LIMIT_GLOBAL_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for user registration to prevent spam accounts.
    |
    */
    'registration' => [
        'max_attempts' => env('RATE_LIMIT_REGISTRATION_ATTEMPTS', 3),
        'decay_minutes' => env('RATE_LIMIT_REGISTRATION_DECAY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for search endpoints to prevent abuse.
    |
    */
    'search' => [
        'max_attempts' => env('RATE_LIMIT_SEARCH_ATTEMPTS', 30),
        'decay_minutes' => env('RATE_LIMIT_SEARCH_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for file upload endpoints.
    |
    */
    'upload' => [
        'max_attempts' => env('RATE_LIMIT_UPLOAD_ATTEMPTS', 10),
        'decay_minutes' => env('RATE_LIMIT_UPLOAD_DECAY', 1),
    ],

];

