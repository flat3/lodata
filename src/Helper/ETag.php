<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

/**
 * ETag
 * @package Flat3\Lodata\Helper
 */
class ETag
{
    /**
     * Generate a SHA 256 hash of the provided input array
     * @param  array  $input  Input
     * @return string Hash value
     */
    public static function hash(array $input): string
    {
        ksort($input);
        return hash('sha256', serialize($input));
    }
}
