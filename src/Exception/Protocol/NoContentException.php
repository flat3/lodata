<?php

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * No Content Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class NoContentException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NO_CONTENT;
    protected $odataCode = 'no_content';
    protected $message = 'No content';
}
