<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Conflict Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class ConflictException extends ProtocolException
{
    protected $httpCode = Response::HTTP_CONFLICT;
    protected $odataCode = 'Conflict';
    protected $message = 'Conflict';
}
