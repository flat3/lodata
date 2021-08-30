<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Property;

/**
 * Properties
 * @package Flat3\Lodata\Helper
 */
class Properties extends ObjectArray
{
    protected $types = [Property::class];
}