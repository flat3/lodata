<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Not Found Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class NotFoundException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_FOUND;
    protected $odataCode = 'not_found';
    protected $message = 'Not found';
}
