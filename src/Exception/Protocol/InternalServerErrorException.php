<?php

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

class InternalServerErrorException extends ProtocolException
{
    protected $httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
    protected $odataCode = 'internal_server_error';
    protected $message = 'Internal server error';
}
