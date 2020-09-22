<?php

namespace Flat3\OData\Exception;

use Illuminate\Http\Response;

class InternalErrorException extends ProtocolException
{
    public function __construct($message = 'Internal error')
    {
        parent::__construct($message, Response::HTTP_NOT_IMPLEMENTED);
    }
}
