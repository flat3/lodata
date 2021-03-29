<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Interfaces\EmitInterface;
use Illuminate\Support\Str;

/**
 * Batch
 * @package Flat3\Lodata\Transaction
 */
abstract class Batch implements EmitInterface
{
    /**
     * Content ID referenced resource URLs
     * @var array $references
     * @internal
     */
    protected $references = [];

    /**
     * Set a referred entity URL value
     * @param  string  $key  Content-ID
     * @param  string  $value  URL
     * @return $this
     */
    protected function setReference(string $key, string $value): self
    {
        $this->references[$key] = $value;

        return $this;
    }

    /**
     * Swap the requested content URL reference
     * @param  Transaction  $transaction
     * @return $this
     */
    protected function maybeSwapContentUrl(Transaction $transaction): self
    {
        $segments = array_filter(explode('/', $transaction->getRequest()->path()));

        if (!Str::startsWith($segments[0], '$')) {
            return $this;
        }

        $contentId = substr($segments[0], 1);

        if (in_array($contentId, ['batch', 'crossjoin', 'all', 'entity', 'root', 'id', 'metadata'])) {
            return $this;
        }

        if (!array_key_exists($contentId, $this->references)) {
            throw new BadRequestException('missing_reference', 'The requested reference request was not found');
        }

        $transaction->getRequest()->setPath(
            parse_url($this->references[$contentId], PHP_URL_PATH).$contentId
        );

        return $this;
    }

    /**
     * Get the response headers
     * @param  Response  $response
     * @return array
     */
    protected function getResponseHeaders(Response $response): array
    {
        $headers = [];

        foreach ($response->headers->allPreserveCaseWithoutCookies() as $key => $values) {
            if (Str::contains(strtolower($key), ['date', 'cache-control', 'odata-version'])) {
                continue;
            }

            $key = strtolower($key);

            $headers[$key] = $headers[$key] ?? [];

            foreach ($values as $value) {
                $headers[$key][] = $value;
            }
        }

        return $headers;
    }
}