<?php

namespace Flat3\OData\Exception\Protocol;

use Illuminate\Http\Response;

class NotFoundException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_FOUND;
    protected $odataCode = 'not_found';
    protected $message = 'Not found';
}
