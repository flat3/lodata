<?php

namespace Flat3\OData\Tests\Unit\Queries\EntitySet\Filter\Functions;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class Substring extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_substr()
    {
        $this->assertNotImplemented(
            Request::factory()
                ->path('/airports')
                ->filter("substring(code,0,2) eq 'lh'")
        );
    }
}
