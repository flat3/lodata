<?php

namespace Flat3\Lodata\Middleware;

use Closure;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;

class Authentication extends AuthenticateWithBasicAuth
{
    public function handle($request, Closure $next, $guard = null, $field = null)
    {
        return parent::handle($request, $next, $guard, $field);
    }
}
