<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Transaction\Metadata\Full;
use Flat3\Lodata\Transaction\Metadata\Minimal;
use Flat3\Lodata\Transaction\Metadata\None;

/**
 * Class Metadata
 *
 * https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheAmountofControlInforma
 */
abstract class Metadata
{
    public const name = '';
    protected $requiredProperties = [];

    /** @var Version $version */
    private $version;

    public function __construct(Version $version)
    {
        $this->version = $version;
    }

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

    public function __toString()
    {
        return $this::name;
    }

    public function getContainer(): MetadataContainer
    {
        return new MetadataContainer($this);
    }

    public function getRequiredProperties(): array
    {
        return $this->requiredProperties;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
