<?php

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

class MethodNotAllowedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_METHOD_NOT_ALLOWED;
    protected $odataCode = 'method_not_allowed';
    protected $message = 'Method not allowed';
}
