<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Exception\Protocol\BadRequestException;

/**
 * Version
 * @package Flat3\Lodata\Transaction
 */
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

    /**
     * Get version
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return mixed|string
     * @internal
     */
    public function __toString()
    {
        return $this->version;
    }
}
