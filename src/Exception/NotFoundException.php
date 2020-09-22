<?php

namespace Flat3\OData\Exception;

use Illuminate\Http\Response;

class NotFoundException extends ProtocolException
{
    public function __construct($message = 'Not found')
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }
}
