<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class NotImplementedTest extends TestCase
{
    public function test_rejects_bad_method()
    {
        $this->assertNotImplemented(
            Request::factory()
                ->query('$compute', 'test')
        );
    }
}
