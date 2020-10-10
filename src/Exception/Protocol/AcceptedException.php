<?php

namespace Flat3\OData\Exception\Protocol;

use Illuminate\Http\Response;

class AcceptedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_ACCEPTED;
    protected $odataCode = 'accepted';
    protected $message = 'Accepted';
}
