<?php

namespace Flat3\Lodata\Exception\Protocol;

use Flat3\Lodata\Controller\Response;
use Illuminate\Contracts\Support\Responsable;
use RuntimeException;

/**
 * Protocol Exception
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ErrorResponseBody
 * @package Flat3\Lodata\Exception\Protocol
 */
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

    /**
     * Generate a new protocol exception
     * @param  string|null  $code  OData code
     * @param  string|null  $message  OData message
     * @return ProtocolException
     */
    public static function factory(string $code = null, string $message = null): self
    {
        /** @phpstan-ignore-next-line */
        return new static($code, $message);
    }

    /**
     * Set the OData error code
     * @param  string  $code  Code
     * @return $this
     */
    public function code(string $code): self
    {
        $this->odataCode = $code;
        return $this;
    }

    /**
     * Set the OData error message
     * @param  string  $message  Message
     * @return $this
     */
    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set the OData error target
     * @param  string  $target  Target
     * @return $this
     */
    public function target(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Set the OData error details
     * @param  string  $details  Details
     * @return $this
     */
    public function details(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    /**
     * Set the OData inner error
     * @param  string  $inner  Inner error
     * @return $this
     */
    public function inner(string $inner): self
    {
        $this->inner = $inner;
        return $this;
    }

    /**
     * Set a header on the outgoing response
     * @param  string  $key  Key
     * @param  string  $value  Value
     * @return $this
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Serialize this error
     * @return array
     */
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

    /**
     * Convert this exception to a Symfony error
     * @return array
     */
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

    /**
     * Convert this exception to a Symfony response
     * @param  null  $request  Request
     * @return Response Response
     */
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

        $response->setProtocolVersion('1.1');
        $response->setStatusCode($this->httpCode);
        $response->headers->replace($this->headers);
        $response->headers->set('content-type', 'application/json');

        return $response;
    }
}
