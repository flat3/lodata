<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Not Implemented Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class NotImplementedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_IMPLEMENTED;
    protected $odataCode = 'not_implemented';
    protected $message = 'Not implemented';
}
