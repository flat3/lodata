<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Not Acceptable Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class NotAcceptableException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_ACCEPTABLE;
    protected $odataCode = 'not_acceptable';
    protected $message = 'Not acceptable';
}
