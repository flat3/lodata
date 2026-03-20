<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityEach;

use Flat3\Lodata\Tests\Drivers\WithKeyedCollectionDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group keyed-collection
 */
#[Group('keyed-collection')]
class KeyedCollectionTest extends EntityEach
{
    use WithKeyedCollectionDriver;
}
