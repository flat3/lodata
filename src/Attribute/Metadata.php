<?php

namespace Flat3\OData\Attribute;

use Flat3\OData\Attribute\Metadata\Full;
use Flat3\OData\Attribute\Metadata\Minimal;
use Flat3\OData\Attribute\Metadata\None;
use Flat3\OData\Exception\Protocol\NotAcceptableException;

/**
 * Class Metadata
 *
 * https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheAmountofControlInforma
 */
abstract class Metadata
{
    public const name = '';
    public const required = [];

    /** @var Version $version */
    private $version;

    public function __construct(Version $version)
    {
        $this->version = $version;
    }

    public static function factory(MediaType $mediaType, Version $version): self
    {
        $type = $mediaType->getParameter('odata.metadata');

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

        throw new NotAcceptableException('invalid_metadata_format',
            sprintf('An invalid metadata format (%s) was specified', $type));
    }

    public function __toString()
    {
        return $this::name;
    }

    /**
     * Filter the response metadata based on the requested metadata type
     *
     * https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheAmountofControlInforma
     *
     * @param  array  $inputMetadata
     *
     * @return array
     */
    public function filter(array $inputMetadata): array
    {
        // Filter out metadata that should not be returned
        if ($this::required) {
            $inputMetadata = array_intersect_key($inputMetadata, array_flip($this::required));
        }

        // Append the control information prefix to the metadata keys
        $requestedODataVersion = (string) $this->version;
        $outputMetadata = [];
        foreach ($inputMetadata as $key => $value) {
            if (version_compare('4.0', $requestedODataVersion, '=')) {
                $outputMetadata['@odata.'.$key] = $value;
            } else {
                $outputMetadata['@'.$key] = $value;
            }
        }

        return $outputMetadata;
    }
}
