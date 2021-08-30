<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Operation\Argument;

/**
 * Arguments
 * @package Flat3\Lodata\Helper
 */
class Arguments extends ObjectArray
{
    protected $types = [Argument::class];
}