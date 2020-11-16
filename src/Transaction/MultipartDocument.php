<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Helper\Url;
use Flat3\Lodata\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Multipart Document
 * @package Flat3\Lodata\Transaction
 * @link https://tools.ietf.org/html/rfc2046
 */
class MultipartDocument
{
    protected $headers = [];

    protected $body = '';

    /**
     * @var self[] $documents
     * @internal
     */
    protected $documents = [];

    public function getContentType(): ?MediaType
    {
        $contentType = new MediaType();

        if (!array_key_exists('content-type', $this->headers)) {
            $contentType->parse('text/plain');

            return $contentType;
        }

        $contentType->parse($this->headers['content-type'][0]);

        return $contentType;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        if ($this->getContentType()->getType() === 'multipart/mixed') {
            $this->parseDocuments($this->body);
        }

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function parseDocuments(string $data): self
    {
        $boundary = $this->getContentType()->getParameter('boundary');
        $delimiter = '--'.$boundary;
        $documents = explode($delimiter, $data);

        // Preamble
        array_shift($documents);

        // Epilogue
        array_pop($documents);

        $documents = array_map('ltrim', $documents);

        foreach ($documents as $document) {
            $multipart = new self();

            list($headers, $body) = array_map('trim', explode("\r\n\r\n", $document, 2));

            foreach (explode("\r\n", $headers) as $header) {
                list($key, $value) = explode(': ', $header, 2);
                $key = strtolower($key);
                $multipart->headers[$key] = $multipart->headers[$key] ?? [];
                $multipart->headers[$key][] = $value;
            }

            $multipart->setBody($body);
            $this->documents[] = $multipart;
        }

        return $this;
    }

    public function toRequest(): Request
    {
        $httpRequest = explode("\r\n", $this->getBody());
        $requestLine = array_shift($httpRequest);

        list($method, $requestURI, $httpVersion) = array_pad(explode(' ', $requestLine), 3, '');

        switch (true) {
            case Str::startsWith($requestURI, '/'):
                $uri = Url::http_build_url(
                    ServiceProvider::endpoint(),
                    $requestURI,
                    Url::HTTP_URL_REPLACE
                );
                break;

            case Str::startsWith($requestURI, '$'):
                $uri = $requestURI;
                break;

            default:
                $uri = Url::http_build_url(
                    ServiceProvider::endpoint(),
                    $requestURI,
                    Url::HTTP_URL_JOIN_PATH
                );
                break;
        }

        $headers = [];

        while ($httpRequest) {
            $line = array_shift($httpRequest);

            if (!$line) {
                break;
            }

            list($key, $value) = explode(': ', $line, 2);

            $headers[$key] = $value;
        }

        $body = implode("\r\n", $httpRequest);

        $request = Request::create($uri, $method, [], [], [], [], $body);
        $request->headers->replace($headers);

        return $request;
    }
}