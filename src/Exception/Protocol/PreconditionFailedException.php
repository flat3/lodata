<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Precondition Failed Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class PreconditionFailedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_PRECONDITION_FAILED;
    protected $odataCode = 'precondition_failed';
    protected $message = 'Precondition failed';
}
