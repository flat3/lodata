<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Transaction\MediaType;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use ZBateson\StreamDecorators\Base64Stream;

/**
 * Stream
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Stream extends Primitive
{
    const identifier = 'Edm.Stream';

    /** @var ?string $value */
    protected $value;

    /** @var MediaType $contentType */
    protected $contentType;

    /** @var Uri $readLink */
    protected $readLink;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return sprintf("'%s'", base64_encode($this->value));
    }

    public function toJson()
    {
        if (null === $this->value) {
            return null;
        }

        return (string) base64_encode($this->value);
    }

    public function set($value): self
    {
        $this->value = $this->maybeNull(null === $value ? null : $value);

        return $this;
    }

    public function emitStream(Transaction $transaction): void
    {
        if (!is_resource($this->value)) {
            $transaction->sendOutput(json_encode($this->value));
            return;
        }

        $output = fopen('php://output', 'w');
        $base64 = new Base64Stream(Utils::streamFor($output));

        /** @var resource $resource */
        $resource = $this->value;

        while (!feof($resource)) {
            echo $base64->write(fread($resource, 512));
        }

        $base64->close();
    }

    public function emitJson(Transaction $transaction): void
    {
        $transaction->sendOutput('"');
        $this->emitStream($transaction);
        $transaction->sendOutput('"');
    }

    /**
     * Set the content type of this property
     * @param  MediaType  $contentType  Content type
     * @return $this Property
     */
    public function setContentType(MediaType $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Get the content type of this property
     * @return MediaType|null Content type
     */
    public function getContentType(): ?MediaType
    {
        return $this->contentType;
    }

    /**
     * Set the read link for this property
     * @param  Uri  $readLink  Read link
     * @return $this Property
     */
    public function setReadLink(Uri $readLink): self
    {
        $this->readLink = $readLink;

        return $this;
    }

    /**
     * Get the read link for this property
     * @return Uri|null Read link
     */
    public function getReadLink(): ?Uri
    {
        return $this->readLink;
    }
}
