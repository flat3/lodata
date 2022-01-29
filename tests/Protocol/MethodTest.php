<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class MethodTest extends TestCase
{
    public function test_rejects_bad_method()
    {
        $this->assertMethodNotAllowed(
            (new Request)
                ->method('PATCH')
        );
    }
}
