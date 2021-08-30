<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Annotation\Reference;

/**
 * References
 * @package Flat3\Lodata\Helper
 */
class References extends ObjectArray
{
    protected $types = [Reference::class];
}