<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use JsonException;

/**
 * JSON
 * @package Flat3\Lodata\Helper
 */
final class JSON
{
    /**
     * Decode a JSON string
     * @param  string  $value
     * @return mixed
     * @throws JsonException
     */
    public static function decode(string $value)
    {
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Encode a JSON string
     * @param $value
     * @return string
     * @throws JsonException
     */
    public static function encode($value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}