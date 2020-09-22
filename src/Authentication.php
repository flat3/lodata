<?php

namespace Flat3\OData;

use Closure;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;

class Authentication extends AuthenticateWithBasicAuth
{
    public function handle($request, Closure $next, $guard = null, $field = null)
    {
        if (defined('PHPUNIT_ODATA_TESTING') && PHPUNIT_ODATA_TESTING) {
            return $next($request);
        }
        return parent::handle($request, $next, $guard, $field);
    }
}
