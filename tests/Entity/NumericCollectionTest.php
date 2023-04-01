<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;

/**
 * @group numeric-collection
 */
class NumericCollectionTest extends Entity
{
    use WithNumericCollectionDriver;

    public function test_read_alternative_key()
    {
        $this->expectNotToPerformAssertions();
    }
}
