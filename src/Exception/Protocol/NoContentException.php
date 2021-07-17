<?php

declare(strict_types=1);

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
    protected $suppressContent = true;
}
