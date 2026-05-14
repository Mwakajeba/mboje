<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Create a new middleware instance.
     */
    public function __construct()
    {
        // Get trusted proxies from environment
        // Use '*' to trust all proxies (useful for load balancers like AWS ELB, Cloudflare, etc.)
        // Or specify comma-separated list of IP addresses
        $proxies = env('TRUSTED_PROXIES', '*');
        
        // If '*' is set, trust all proxies (useful for load balancers)
        // Otherwise, parse comma-separated list of IP addresses
        if ($proxies === '*') {
            $this->proxies = '*';
        } else {
            $this->proxies = array_filter(array_map('trim', explode(',', $proxies)));
        }
    }
}

