<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class NotImplementedTest extends TestCase
{
    public function test_rejects_bad_method()
    {
        $this->assertNotImplemented(
            (new Request)
                ->query('$compute', 'test')
        );
    }
}
