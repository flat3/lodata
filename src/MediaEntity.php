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

    /**
     * Get a media entity
     * @param  Transaction  $transaction
     * @param  ContextInterface|null  $context
     * @return Response
     */
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

    /**
     * Set the content type of this entity
     * @param  MediaType  $contentType  Content type
     * @return $this Entity
     */
    public function setContentType(MediaType $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Get the content type of this entity
     * @return MediaType|null Content type
     */
    public function getContentType(): ?MediaType
    {
        return $this->contentType;
    }

    /**
     * Set the read link for this entity
     * @param  Uri  $readLink  Read link
     * @return $this Entity
     */
    public function setReadLink(Uri $readLink): self
    {
        $this->readLink = $readLink;

        return $this;
    }

    /**
     * Get the read link for this entity
     * @return Uri|null Read link
     */
    public function getReadLink(): ?Uri
    {
        return $this->readLink;
    }
}