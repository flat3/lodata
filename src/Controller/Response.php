<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Transaction\MediaType;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Response
 * @package Flat3\Lodata\Controller
 */
class Response extends StreamedResponse
{
    /**
     * The resource responding to this request
     * @var ResourceInterface $resource
     * @internal
     */
    protected $resource;

    /**
     * Get the resource responding to this request
     * @return ResourceInterface|null
     */
    public function getResource(): ?ResourceInterface
    {
        return $this->resource;
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
     * Send the results to the client, implementing OData error handling
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358909
     * @return Response
     */
    public function sendContent()
    {
        try {
            return parent::sendContent();
        } catch (ProtocolException $e) {
            flush();
            ob_flush();
            printf('OData-Error: '.json_encode($e->toError(), JSON_UNESCAPED_SLASHES));
        }

        return $this;
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
        return MediaType::factory()->parse($this->headers->get('content-type'));
    }

    /**
     * Encode this response as JSON
     * @return false|string
     * @internal
     */
    public function toJson()
    {
        return json_encode([
            'status' => $this->statusCode,
            'headers' => $this->headers->all(),
        ], JSON_UNESCAPED_SLASHES);
    }
}