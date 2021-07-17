<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\String;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Matches Pattern
 * @package Flat3\Lodata\Expression\Node\Func\String
 */
class MatchesPattern extends Func
{
    public const symbol = 'matchesPattern';
    public const arguments = 2;
}
