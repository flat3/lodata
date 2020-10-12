<?php

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

class ForbiddenException extends ProtocolException
{
    protected $httpCode = Response::HTTP_FORBIDDEN;
    protected $odataCode = 'forbidden';
    protected $message = 'Forbidden';
}
