<?php

namespace Flat3\Lodata\Helper;

class ETag
{
    public static function hash(array $input): string
    {
        ksort($input);
        return hash('sha256', serialize($input));
    }
}
