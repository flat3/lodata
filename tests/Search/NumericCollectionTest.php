<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Search;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;

/**
 * @group numeric-collection
 */
class NumericCollectionTest extends Search
{
    use WithNumericCollectionDriver;
}
