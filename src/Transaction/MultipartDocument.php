<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Url;
use Flat3\Lodata\Endpoint;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Multipart Document
 * @package Flat3\Lodata\Transaction
 * @link https://tools.ietf.org/html/rfc2046
 */
class MultipartDocument
{
    /**
     * @var array Document headers
     */
    protected $headers = [];

    /**
     * @var string Document body
     */
    protected $body = '';

    /**
     * @var self[] $documents Documents
     */
    protected $documents = [];

    /**
     * Get the content type of this document
     * @return MediaType|null
     */
    public function getContentType(): ?MediaType
    {
        $contentType = new MediaType();

        if (!array_key_exists(Constants::contentType, $this->headers)) {
            $contentType->parse(MediaType::text);

            return $contentType;
        }

        $contentType->parse($this->headers[Constants::contentType][0]);

        return $contentType;
    }

    /**
     * Set the headers of this document
     * @param  array  $headers
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get the headers of this document
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set the body of this document
     * @param  string  $body
     * @return $this
     */
    public function setBody(string $body): self
    {
        $this->body = $body;

        if ($this->getContentType()->getFullType() === MediaType::multipartMixed) {
            $this->parseDocuments();
        }

        return $this;
    }

    /**
     * Get the body of this document
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get all the documents included inside this document
     * @return MultipartDocument[]
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * Parse the body of this document into its sub-documents
     * @return $this
     */
    public function parseDocuments(): self
    {
        $boundary = $this->getContentType()->getParameter('boundary');
        $delimiter = '--'.$boundary;
        $documents = explode($delimiter, $this->body);

        // Preamble
        array_shift($documents);

        // Epilogue
        array_pop($documents);

        $documents = array_map('ltrim', $documents);

        foreach ($documents as $document) {
            $multipart = new self();

            list($headers, $body) = array_map(
                'trim',
                explode(
                    "\n\n",
                    str_replace("\r\n", "\n", $document),
                    2
                )
            );

            foreach (explode("\n", $headers) as $header) {
                list($key, $value) = explode(':', $header, 2);
                $key = strtolower($key);
                $multipart->headers[$key] = $multipart->headers[$key] ?? [];
                $multipart->headers[$key][] = trim($value);
            }

            $multipart->setBody($body);
            $this->documents[] = $multipart;
        }

        return $this;
    }

    /**
     * Convert this document to a Request
     * @return Request
     * @throws BindingResolutionException
     */
    public function toRequest(): Request
    {
        $httpRequest = explode("\n", $this->body);
        $requestLine = array_shift($httpRequest);

        list($method, $requestURI, $httpVersion) = array_pad(explode(' ', $requestLine), 3, '');

        $endpoint = app(Endpoint::class)->endpoint();
        switch (true) {
            case Str::startsWith($requestURI, '/'):
                $uri = Url::http_build_url(
                    $endpoint,
                    $requestURI,
                    Url::HTTP_URL_REPLACE
                );
                break;

            case Str::startsWith($requestURI, '$'):
                $uri = $requestURI;
                break;

            default:
                $uri = Url::http_build_url(
                    $endpoint,
                    $requestURI,
                    Url::HTTP_URL_JOIN_PATH | Url::HTTP_URL_JOIN_QUERY
                );
                break;
        }

        $headers = [];

        while ($httpRequest) {
            $line = array_shift($httpRequest);

            if (!$line) {
                break;
            }

            list($key, $value) = explode(':', $line, 2);

            $headers[$key] = trim($value);
        }

        $body = implode("\n", $httpRequest);

        $request = Request::create($uri, $method, [], [], [], [], $body);
        $request->headers->replace($headers);

        return $request;
    }
}
