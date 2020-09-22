<?php

namespace Flat3\OData\Exception\Protocol;

use Illuminate\Http\Response;

class NotImplementedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_IMPLEMENTED;
    protected $odataCode = 'not_implemented';
    protected $message = 'Not implemented';
}
