<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Tests\Drivers\WithKeyedCollectionDriver;

/**
 * @group keyed-collection
 */
class KeyedCollectionTest extends Pagination
{
    use WithKeyedCollectionDriver;
}
