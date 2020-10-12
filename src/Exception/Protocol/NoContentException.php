<?php

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

class NoContentException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NO_CONTENT;
    protected $odataCode = 'no_content';
    protected $message = 'No content';
}
