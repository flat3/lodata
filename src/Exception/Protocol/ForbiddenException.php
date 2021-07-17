<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Forbidden Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class ForbiddenException extends ProtocolException
{
    protected $httpCode = Response::HTTP_FORBIDDEN;
    protected $odataCode = 'forbidden';
    protected $message = 'Forbidden';
}
