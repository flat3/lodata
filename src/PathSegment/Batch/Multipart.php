<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment\Batch;

use Flat3\Lodata\Controller\Request;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\StreamInterface;
use Flat3\Lodata\PathSegment\Batch;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\MultipartDocument;
use Illuminate\Support\Str;

/**
 * Multipart
 * @package Flat3\Lodata\PathSegment\Batch
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_MultipartBatchFormat
 */
class Multipart extends Batch implements StreamInterface
{
    /**
     * @var MultipartDocument[] $documents Discovered documents
     * @internal
     */
    protected $documents = [];

    /**
     * @var string[] $boundaries Nested boundaries
     * @internal
     */
    protected $boundaries = [];

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $contentType = $transaction->getProvidedContentType();

        if (!$contentType->getParameter('boundary')) {
            throw new BadRequestException('missing_boundary', 'The provided content type had no boundary parameter');
        }

        array_unshift($this->boundaries, Str::uuid());
        $transaction->sendContentType(
            MediaType::factory()
                ->parse(MediaType::multipartMixed)
                ->setParameter('boundary', $this->boundaries[0])
        );

        $multipart = new MultipartDocument();
        $multipart->setHeaders($transaction->getRequestHeaders());
        $multipart->setBody($transaction->getBody());
        $this->documents[] = $multipart;

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitStream($transaction);
        });
    }

    public function emitStream(Transaction $transaction): void
    {
        $document = array_pop($this->documents);

        foreach ($document->getDocuments() as $document) {
            $transaction->sendOutput(sprintf("\r\n--%s\r\n", $this->boundaries[0]));

            if ($document->getDocuments()) {
                array_unshift($this->boundaries, Str::uuid());

                $transaction->sendOutput(sprintf(
                    "content-type: multipart/mixed;boundary=%s\r\n\r\n",
                    $this->boundaries[0]
                ));

                $this->documents[] = $document;
                $this->emitStream($transaction);

                array_shift($this->boundaries);
            } else {
                $transaction->sendOutput("content-type: application/http\r\n\r\n");
                $requestTransaction = new Transaction();
                $requestTransaction->initialize(new Request($document->toRequest()));

                $response = null;

                try {
                    $this->maybeSwapContentUrl($requestTransaction);
                    $response = $requestTransaction->execute();
                } catch (ProtocolException $e) {
                    $response = $e->toResponse();
                }

                $transaction->sendOutput(sprintf(
                    "HTTP/%s %s %s\r\n",
                    $response->getProtocolVersion(),
                    $response->getStatusCode(),
                    $response->getStatusText()
                ));

                foreach ($this->getResponseHeaders($response) as $key => $values) {
                    foreach ($values as $value) {
                        $transaction->sendOutput($key.': '.$value."\r\n");
                    }
                }

                $transaction->sendOutput("\r\n");
                $response->sendContent();

                $contentId = $requestTransaction->getRequestHeader('content-id');

                if ($contentId && $response->getResource() instanceof ResourceInterface) {
                    $this->setReference($contentId, $response->getResource()->getResourceUrl($requestTransaction));
                }
            }
        }

        $transaction->sendOutput(sprintf("\r\n--%s--\r\n", $this->boundaries[0]));
    }
}