<?php

namespace Flat3\OData\Exception\Protocol;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use RuntimeException;

abstract class ProtocolException extends RuntimeException implements Responsable
{
    protected $httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
    protected $odataCode;
    protected $message;
    protected $target;
    protected $details;
    protected $inner;

    public function __construct($code = 'odata_error', $message = 'OData error')
    {
        parent::__construct($message);
        $this->odataCode = $code;
        $this->message = $message;
    }

    public static function factory(string $code = null, string $message = null)
    {
        return new static($code, $message);
    }

    public function code(string $code)
    {
        $this->odataCode = $code;
        return $this;
    }

    public function message(string $message)
    {
        $this->message = $message;
        return $this;
    }

    public function target(string $target)
    {
        $this->target = $target;
        return $this;
    }

    public function details(string $details)
    {
        $this->details = $details;
        return $this;
    }

    public function inner(string $inner)
    {
        $this->inner = $inner;
        return $this;
    }

    public function toResponse($request): Response
    {
        return new Response(json_encode(array_filter([
            'code' => $this->odataCode,
            'message' => $this->message,
            'target' => $this->target,
            'details' => $this->details,
            'innererror' => $this->inner,
        ]), $this->httpCode));
    }
}
