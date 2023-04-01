<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Count;

use Flat3\Lodata\Tests\Drivers\WithKeyedCollectionDriver;

/**
 * @group keyed-collection
 */
class KeyedCollectionTest extends Count
{
    use WithKeyedCollectionDriver;
}
