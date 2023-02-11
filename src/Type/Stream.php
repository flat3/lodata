<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\JSON;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Property;
use Flat3\Lodata\Transaction\MediaType;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use ZBateson\StreamDecorators\Base64Stream;

/**
 * Stream
 * @package Flat3\Lodata\Type
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
            return Constants::null;
        }

        return sprintf("'%s'", $this->getEncodedValueAsString());
    }

    public function toJson()
    {
        if (null === $this->value) {
            return null;
        }

        return $this->getEncodedValueAsString();
    }

    public function toMixed()
    {
        return $this->value;
    }

    public function getEncodedValueAsString(): string
    {
        if (is_resource($this->value)) {
            rewind($this->value);

            return base64_encode(stream_get_contents($this->value));
        }

        return base64_encode((string) $this->value);
    }

    public function set($value): self
    {
        $this->value = null === $value ? null : $value;

        return $this;
    }

    public function emitStream(Transaction $transaction): void
    {
        if (!is_resource($this->value)) {
            $transaction->sendOutput(JSON::encode($this->value));
            return;
        }

        rewind($this->value);
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

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiString,
            'format' => 'base64url',
        ]);
    }
}
