<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Not Modified Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class NotModifiedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_MODIFIED;
    protected $odataCode = 'not_modified';
    protected $message = 'Not modified';
    protected $suppressContent = true;
}
