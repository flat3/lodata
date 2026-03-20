<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Tests\Drivers\WithKeyedCollectionDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group keyed-collection
 */
#[Group('keyed-collection')]
class KeyedCollectionTest extends Pagination
{
    use WithKeyedCollectionDriver;
}
