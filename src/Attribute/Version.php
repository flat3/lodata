<?php

namespace Flat3\OData\Attribute;

use Flat3\OData\Exception\Protocol\BadRequestException;

class Version
{
    public const version = '4.01';
    public const minVersion = '4.0';
    public const versionHeader = 'odata-version';
    public const maxVersionHeader = 'odata-maxversion';

    private $version;

    public function __construct($version, $maxVersion)
    {
        if ($version && ($version < self::minVersion || $version > self::version)) {
            throw new BadRequestException('version_not_supported',
                sprintf('Requested OData version (%s) is not supported', $version));
        }

        if ($maxVersion && ($maxVersion < self::minVersion || $maxVersion > self::version)) {
            throw new BadRequestException('maxversion_not_supported',
                sprintf('Requested OData max version (%s) is not supported', $version));
        }

        $this->version = ($maxVersion ?: $version) ?: self::version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function __toString()
    {
        return $this->version;
    }
}
