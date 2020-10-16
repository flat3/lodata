<?php

namespace Flat3\Lodata\Exception\Protocol;

use Flat3\Lodata\Controller\Response;
use Illuminate\Contracts\Support\Responsable;
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

    public function __construct(string $code = null, string $message = null)
    {
        if ($code) {
            $this->odataCode = $code;
        }

        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message);
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

    public function toResponse($request = null): Response
    {
        $response = new Response();

        $response->setCallback(function () {
            echo json_encode(array_filter([
                'code' => $this->odataCode,
                'message' => $this->message,
                'target' => $this->target,
                'details' => $this->details,
                'innererror' => $this->inner,
            ]), JSON_UNESCAPED_SLASHES);
        });

        $response->setStatusCode($this->httpCode);
        $response->headers->replace($this->headers);

        return $response;
    }
}
