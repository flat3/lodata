<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\ComputeOrderBy;

use Flat3\Lodata\Tests\Drivers\WithKeyedCollectionDriver;

/**
 * @group keyed-collection
 */
class KeyedCollectionTest extends ComputeOrderBy
{
    use WithKeyedCollectionDriver;
}
