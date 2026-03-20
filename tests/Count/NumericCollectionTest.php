<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Count;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group numeric-collection
 */
#[Group('numeric-collection')]
class NumericCollectionTest extends Count
{
    use WithNumericCollectionDriver;
}
