<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\OrderBy;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;

/**
 * @group numeric-collection
 */
class NumericCollectionTest extends OrderBy
{
    use WithNumericCollectionDriver;
}
