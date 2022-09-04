<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Unauthorized Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class UnauthorizedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_UNAUTHORIZED;
    protected $odataCode = 'unauthorized';
    protected $message = 'Unauthorized';
}
