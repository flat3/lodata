<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Interfaces\EmitInterface;
use Illuminate\Support\Str;

abstract class Batch implements EmitInterface
{
    /**
     * Content ID referenced resource URLs
     * @var array $references
     * @internal
     */
    protected $references = [];

    protected function setReference(string $key, $value): self
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
     * Get the sub-request response headers
     * @param  Transaction  $transaction
     * @return array
     */
    protected function getResponseHeaders(Transaction $transaction): array
    {
        $response = $transaction->getResponse();
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