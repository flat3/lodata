<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Transaction\MediaType;
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
    protected $target = null;
    protected $details = [];
    protected $innerError = [];
    protected $headers = [];
    protected $suppressContent = false;

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
     * @param  string  $code  Details
     * @return $this
     */
    public function addDetail(string $code, string $message, ?string $target = null): self
    {
        $detail = [
            'code' => $code,
            'message' => $message,
        ];

        if ($target) {
            $detail['target'] = $target;
        }

        $this->details[] = $detail;

        return $this;
    }

    /**
     * Set the OData inner error
     * @param  string  $key  Key
     * @param  string  $value  Value
     * @return $this
     */
    public function addInnerError(string $key, string $value): self
    {
        $this->innerError[$key] = $value;

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
            'innererror' => $this->innerError,
            'headers' => $this->headers,
        ]);
    }

    /**
     * Convert this exception to a Symfony error
     * @return array
     */
    public function toError()
    {
        return [
            'code' => $this->odataCode,
            'message' => $this->message,
            'target' => $this->target,
            'details' => $this->details,
            'innererror' => $this->innerError ?: (object) [],
        ];
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
            if ($this->suppressContent) {
                return;
            }

            echo json_encode(['error' => $this->toError()], JSON_UNESCAPED_SLASHES);
        });

        $response->setProtocolVersion('1.1');
        $response->setStatusCode($this->httpCode);
        $response->headers->replace($this->headers);
        $response->headers->set(Constants::contentType, MediaType::json);

        return $response;
    }
}
