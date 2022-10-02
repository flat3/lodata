<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Tests\Drivers\WithCSVDriver;

class CSVTest extends EntityTest
{
    use WithCSVDriver;

    public function test_read_alternative_key()
    {
        $this->expectNotToPerformAssertions();
    }
}