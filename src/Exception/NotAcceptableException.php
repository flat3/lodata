<?php

namespace Flat3\OData\Exception;

use Illuminate\Http\Response;

class NotAcceptableException extends ProtocolException
{
    public function __construct($message = 'Not acceptable')
    {
        parent::__construct($message, Response::HTTP_NOT_ACCEPTABLE);
    }
}
