<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Search;

use Flat3\Lodata\Tests\Drivers\WithKeyedCollectionDriver;

/**
 * @group keyed-collection
 */
class KeyedCollectionTest extends Search
{
    use WithKeyedCollectionDriver;
}
