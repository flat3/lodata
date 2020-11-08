<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Transaction\Metadata\Full;
use Flat3\Lodata\Transaction\Metadata\Minimal;
use Flat3\Lodata\Transaction\Metadata\None;

/**
 * Metadata
 * @link https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheAmountofControlInforma
 * @package Flat3\Lodata\Transaction
 */
abstract class Metadata
{
    public const name = '';
    protected $requiredProperties = [];

    /**
     * OData version
     * @var Version $version
     */
    private $version;

    public function __construct(Version $version)
    {
        $this->version = $version;
    }

    /**
     * Generate a new metadata object
     * @param  string|null  $type
     * @param  Version  $version
     * @return static
     */
    public static function factory(?string $type, Version $version): self
    {
        if (!$type) {
            return new Minimal($version);
        }

        switch ($type) {
            case None::name:
                return new None($version);

            case Minimal::name:
                return new Minimal($version);

            case Full::name:
                return new Full($version);
        }

        throw new NotAcceptableException(
            'invalid_metadata_format',
            sprintf('An invalid metadata format (%s) was specified', $type)
        );
    }

    /**
     * @return string
     * @internal
     */
    public function __toString()
    {
        return $this::name;
    }

    /**
     * Get a new metadata container from this metadata object
     * @return MetadataContainer
     */
    public function getContainer(): MetadataContainer
    {
        return new MetadataContainer($this);
    }

    /**
     * Get the list of properties required by this metadata type
     * @return array
     */
    public function getRequiredProperties(): array
    {
        return $this->requiredProperties;
    }

    /**
     * Get the OData version attached to this metadata type
     * @return Version
     */
    public function getVersion(): Version
    {
        return $this->version;
    }
}
