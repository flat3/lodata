<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Property\Navigation;

use Flat3\Lodata\Expression\Node\Property\Navigation;

/**
 * Navigation property count
 *
 * Represents `$count` applied to a collection-valued navigation property within an
 * expression, for example `passengers/$count` in a `$filter`. The node carries the
 * navigation property it counts and is evaluated to the number of related entities.
 * @package Flat3\Lodata\Expression\Node\Property\Navigation
 */
class Count extends Navigation
{
}
