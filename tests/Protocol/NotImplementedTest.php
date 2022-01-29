<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class NotImplementedTest extends TestCase
{
    public function test_rejects_bad_method()
    {
        $this->assertNotImplemented(
            (new Request)
                ->apply('test')
        );
    }
}
