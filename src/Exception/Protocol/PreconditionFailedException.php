<?php

namespace Flat3\OData\Exception\Protocol;

use Illuminate\Http\Response;

class PreconditionFailedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_PRECONDITION_FAILED;
    protected $odataCode = 'precondition_failed';
    protected $message = 'Precondition failed';
}
