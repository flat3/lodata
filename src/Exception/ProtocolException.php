<?php

namespace Flat3\OData\Exception;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProtocolException extends HttpException
{
    public function __construct(string $message, int $code = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        parent::__construct($code, $message);
    }
}
