<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class MethodTest extends TestCase
{
    public function test_rejects_bad_method()
    {
        $this->assertMethodNotAllowed(
            Request::factory()
                ->method('PATCH')
        );
    }
}
