<?php

declare(strict_types=1);

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\JSON;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Transaction\MediaType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Response
 * @package Flat3\Lodata\Controller
 */
abstract class Response extends StreamedResponse
{
    const httpOkAny = '2XX';
    const httpErrorAny = '4XX';

    /**
     * The resource responding to this request
     * @var ResourceInterface $resource
     */
    protected $resource;

    /**
     * Whether this response streams its output
     * @var bool $streaming
     */
    protected $streaming = true;

    /**
     * The exception that triggered the error response (if applicable).
     * @var Throwable|null
     */
    public $exception;

    public function __construct(?callable $callback = null, int $status = 200, array $headers = [])
    {
        parent::__construct($callback, $status, $headers);

        $this->streaming = config('lodata.streaming', true);
    }

    /**
     * Get the resource responding to this request
     * @return ResourceInterface|null
     */
    public function getResource(): ?ResourceInterface
    {
        return $this->resource;
    }

    /**
     * Set whether this response will stream its output
     * @param  bool  $streaming
     * @return $this
     */
    public function setStreaming(bool $streaming): self
    {
        $this->streaming = $streaming;

        return $this;
    }

    /**
     * Return whether this response uses streaming
     * @return bool Streaming
     */
    public function isStreaming(): bool
    {
        return $this->streaming;
    }

    /**
     * Set both the responding resource and its emitter callback
     * @param  ResourceInterface  $resource  Resource
     * @param  callable  $callback  Callback
     * @return $this
     */
    public function setResourceCallback(ResourceInterface $resource, callable $callback): self
    {
        $this->resource = $resource;
        return $this->setCallback($callback);
    }

    /**
     * Stream the results to the client, implementing OData error handling
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358909
     * @return Response
     */
    public function sendContentStreamed(): Response
    {
        try {
            parent::sendContent();
        } catch (ProtocolException $e) {
            flush();
            ob_flush();
            echo 'OData-error: '.JSON::encode($e->toError());
        }

        return $this;
    }

    /**
     * Buffer the result before sending to the client, to enable clean error reporting
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358909
     * @return Response
     * @throws Throwable
     */
    public function sendContentBuffered(): Response
    {
        try {
            ob_start();
            parent::sendContent();
            echo ob_get_clean();
        } catch (ProtocolException $e) {
            ob_end_clean();
            $response = $e->toResponse();
            $this->setStatusCode($response->getStatusCode());
            $this->headers->replace($response->headers->all());
            $response->sendHeaders();
            $response->sendContentBuffered();
            return $response;
        } catch (Throwable $t) {
            ob_end_clean();
            throw $t;
        }

        return $this;
    }

    /**
     * Support buffered or streaming responses
     * @return $this
     */
    protected function _sendContent(): Response
    {
        if ($this->streaming) {
            $this->headers->set(Constants::trailer, Constants::odataError);
        }

        return $this->streaming ? $this->sendContentStreamed() : $this->sendContentBuffered();
    }

    /**
     * Get the text representation of the HTTP response code
     * @return string
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

    /**
     * Get the response content type
     * @return MediaType|null Content type
     */
    public function getContentType(): ?MediaType
    {
        return (new MediaType)->parse($this->headers->get(Constants::contentType));
    }

    /**
     * Encode this response as JSON
     * @return string
     */
    public function toJson()
    {
        return JSON::encode([
            'status' => $this->statusCode,
            'headers' => $this->headers->all(),
        ]);
    }

    /**
     * Set the exception to attach to the response.
     */
    public function withException(Throwable $e)
    {
        $this->exception = $e;

        return $this;
    }
}