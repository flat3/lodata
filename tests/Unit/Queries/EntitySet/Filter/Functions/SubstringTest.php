<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySet\Filter\Functions;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class SubstringTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_substr()
    {
        $this->assertNotImplemented(
            (new Request)
                ->path('/airports')
                ->filter("substring(code,0,2) eq 'lh'")
        );
    }
}
