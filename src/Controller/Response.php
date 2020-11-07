<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Response
 * @package Flat3\Lodata\Controller
 */
class Response extends StreamedResponse
{
    /**
     * Send the results to the client, implementing OData error handling
     * @return $this|Response
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