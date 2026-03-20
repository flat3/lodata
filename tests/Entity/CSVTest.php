<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Tests\Drivers\WithCSVDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group csv
 */
#[Group('csv')]
class CSVTest extends Entity
{
    use WithCSVDriver;

    public function test_read_alternative_key()
    {
        $this->expectNotToPerformAssertions();
    }
}
