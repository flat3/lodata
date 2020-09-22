<?php

namespace Flat3\OData\Exception;

use Illuminate\Http\Response;

class NotImplementedException extends ProtocolException
{
    public function __construct($message = 'Not implemented')
    {
        parent::__construct($message, Response::HTTP_NOT_IMPLEMENTED);
    }
}
