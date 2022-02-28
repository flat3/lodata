<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Transaction\MediaType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Batch
 * @package Flat3\Lodata\PathSegment
 */
abstract class Batch implements PipeInterface, ResponseInterface
{
    /**
     * Content ID referenced resource URLs
     * @var array $references
     */
    protected $references = [];

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentSegment !== '$batch') {
            throw new PathNotHandledException();
        }

        if ($argument || $nextSegment) {
            throw new BadRequestException('batch_argument', '$batch must be the only argument in the path');
        }

        $transaction->assertMethod(Request::METHOD_POST);

        $contentType = $transaction->getProvidedContentType();

        switch ($contentType->getFullType()) {
            case MediaType::multipartMixed:
                return new Batch\Multipart();

            case MediaType::json:
                return new Batch\JSON();

            default:
                throw new NotAcceptableException(
                    'unknown_batch_type',
                    'The requested batch content type was not known'
                );
        }
    }

    /**
     * Set a referred entity URL value
     * @param  string  $key  Content-ID
     * @param  string  $value  URL
     * @return Batch
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
            if (Str::contains(strtolower($key), [Constants::date, Constants::cacheControl, Constants::odataVersion])) {
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
