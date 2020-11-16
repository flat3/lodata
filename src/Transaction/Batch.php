<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Lexer;
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

    /**
     * Swap the requested content URL reference
     * @param  Transaction  $transaction
     * @return $this
     */
    protected function maybeSwapContentUrl(Transaction $transaction): self
    {
        $transactionPath = $transaction->getRequest()->path();

        $lexer = new Lexer($transactionPath);

        if (!$lexer->maybeChar('$')) {
            return $this;
        }

        try {
            $contentId = $lexer->number();
        } catch (LexerException $e) {
            throw new BadRequestException(
                'bad_content_id',
                'The provided content ID reference was not a number'
            );
        }

        $transaction->getRequest()->setPath(
            parse_url($this->references[$contentId], PHP_URL_PATH).$lexer->remaining()
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