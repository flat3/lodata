<?php

namespace Flat3\OData\Exception;

use Illuminate\Http\Response;

class BadRequestException extends ProtocolException
{
    public function __construct($message = 'Bad request')
    {
        parent::__construct($message, Response::HTTP_BAD_REQUEST);
    }
}
