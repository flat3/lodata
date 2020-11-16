<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Response
 * @package Flat3\Lodata\Controller
 */
class Response extends StreamedResponse
{
    protected $segment;

    public function setSegment(ResourceInterface $segment): self
    {
        $this->segment = $segment;

        return $this;
    }

    public function getSegment(): ResourceInterface
    {
        return $this->segment;
    }

    /**
     * Send the results to the client, implementing OData error handling
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358909
     * @return $this|Response
     */
    public function sendContent()
    {
        try {
            return parent::sendContent();
        } catch (ProtocolException $e) {
            $this->emitError($e);
        } catch (Throwable $e) {
            $this->emitError(
                new InternalServerErrorException(
                    'unknown_error',
                    'An unknown internal error has occurred'
                )
            );
        }

        return $this;
    }

    /**
     * Close the response and emit an error
     * @param  Throwable  $e
     * @return $this
     */
    public function emitError(Throwable $e): self
    {
        flush();
        ob_flush();
        printf('OData-Error: '.json_encode($e->toError(), JSON_UNESCAPED_SLASHES));

        return $this;
    }

    public function getStatusText(): string
    {
        return $this->statusText;
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