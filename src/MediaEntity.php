<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Transaction\MediaType;
use GuzzleHttp\Psr7\Uri;

class MediaEntity extends Entity
{
    /** @var MediaType $contentType */
    protected $contentType;

    /** @var Uri $readLink */
    protected $readLink;

    public function get(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $response = parent::get($transaction, $context);

        if ($this->contentType) {
            $this->metadata['mediaContentType'] = (string) $this->contentType;
        }

        if ($this->readLink) {
            $this->metadata['mediaReadLink'] = (string) $this->readLink;
        }

        return $response;
    }

    public function setContentType(MediaType $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getContentType(): ?MediaType
    {
        return $this->contentType;
    }

    public function setReadLink(Uri $readLink): self
    {
        $this->readLink = $readLink;

        return $this;
    }

    public function getReadLink(): ?Uri
    {
        return $this->readLink;
    }
}