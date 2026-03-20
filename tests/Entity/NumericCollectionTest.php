<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group numeric-collection
 */
#[Group('numeric-collection')]
class NumericCollectionTest extends Entity
{
    use WithNumericCollectionDriver;

    public function test_read_alternative_key()
    {
        $this->expectNotToPerformAssertions();
    }
}
