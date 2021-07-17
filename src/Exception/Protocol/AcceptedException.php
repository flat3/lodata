<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Accepted Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class AcceptedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_ACCEPTED;
    protected $odataCode = 'accepted';
    protected $message = 'Accepted';
}
