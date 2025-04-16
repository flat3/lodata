<?php

namespace Flat3\Lodata\PathSegment\Metadata;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Endpoint;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\StreamInterface;
use Flat3\Lodata\PathSegment\Metadata;
use Flat3\Lodata\Transaction\MediaType;
use RuntimeException;

class CachedXML extends Metadata implements StreamInterface
{
    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->sendContentType((new MediaType)->parse(MediaType::xml));

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitStream($transaction);
        });
    }

    public function emitStream(Transaction $transaction): void
    {
        $path = app(Endpoint::class)->cachedMetadataXMLPath();
        if (!file_exists($path)) {
            throw new RuntimeException("Metadata file not found: {$path}");
        }
        $content = file_get_contents($path);
        if (false === $content) {
            throw new RuntimeException("Failed to read metadata file: {$path}");
        }
        $transaction->sendOutput($content);
    }
}