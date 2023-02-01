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
    public const v4_01 = '4.01';
    public const v4_0 = '4.0';

    private $version;

    public function __construct($version, $maxVersion)
    {
        if ($version && ($version < self::v4_0 || $version > self::v4_01)) {
            throw new BadRequestException(
                'version_not_supported',
                sprintf('Requested OData version (%s) is not supported', $version)
            );
        }

        if ($maxVersion && ($maxVersion < self::v4_0 || $maxVersion > self::v4_01)) {
            throw new BadRequestException(
                'maxversion_not_supported',
                sprintf('Requested OData max version (%s) is not supported', $version)
            );
        }

        $this->version = ($maxVersion ?: $version) ?: config('lodata.version', self::v4_01);
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
     * Prefix the provided parameter for pre 4.01 version of OData
     * @param  string  $parameter
     * @return string
     */
    public function prefixParameter(string $parameter): string
    {
        return $this->version === Version::v4_01 ? $parameter : 'odata.'.$parameter;
    }

    /**
     * @return mixed|string
     */
    public function __toString()
    {
        return $this->version;
    }
}
