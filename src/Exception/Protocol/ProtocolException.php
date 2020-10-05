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
    protected $headers = [];

    public function __construct($code = 'odata_error', $message = 'OData error')
    {
        parent::__construct($message);
        $this->odataCode = $code;
        $this->message = $message;
    }

    public static function factory(string $code = null, string $message = null): self
    {
        return new static($code, $message);
    }

    public function code(string $code): self
    {
        $this->odataCode = $code;
        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function target(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    public function details(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function inner(string $inner): self
    {
        $this->inner = $inner;
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function serialize()
    {
        return array_filter([
            'httpCode' => $this->httpCode,
            'odataCode' => $this->odataCode,
            'message' => $this->message,
            'target' => $this->target,
            'details' => $this->details,
            'inner' => $this->inner,
            'headers' => $this->headers,
        ]);
    }

    public function toError()
    {
        return array_filter([
            'code' => $this->odataCode,
            'message' => $this->message,
            'target' => $this->target,
            'details' => $this->details,
            'inner' => $this->inner,
        ]);
    }

    public function toResponse($request): Response
    {
        return new Response(json_encode(array_filter([
            'code' => $this->odataCode,
            'message' => $this->message,
            'target' => $this->target,
            'details' => $this->details,
            'innererror' => $this->inner,
        ])), $this->httpCode, $this->headers);
    }
}
