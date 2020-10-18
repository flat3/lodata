<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Response extends StreamedResponse
{
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

    public function toJson()
    {
        return json_encode([
            'status' => $this->statusCode,
            'headers' => $this->headers->all(),
        ], JSON_UNESCAPED_SLASHES);
    }
}