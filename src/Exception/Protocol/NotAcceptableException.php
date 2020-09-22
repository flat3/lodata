<?php

namespace Flat3\OData\Exception\Protocol;

use Illuminate\Http\Response;

class NotAcceptableException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_ACCEPTABLE;
    protected $odataCode = 'not_acceptable';
    protected $message = 'Not acceptable';
}
