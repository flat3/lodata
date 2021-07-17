<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Method Not Allowed Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class MethodNotAllowedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_METHOD_NOT_ALLOWED;
    protected $odataCode = 'method_not_allowed';
    protected $message = 'Method not allowed';
}
