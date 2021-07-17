<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Illuminate\Support\Str;

/**
 * Laravel
 * Backwards compatibility functions
 * @package Flat3\Lodata\Helper
 */
class Laravel
{
    public static function afterLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, (string) $search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }

    public static function beforeLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $pos = mb_strrpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return Str::substr($subject, 0, $pos);
    }
}