<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class BasicAuthTest extends TestCase
{
    public function test_requires_basic_auth()
    {
        $this->withMiddleware();

        $this->assertNotAuthenticated(
            Request::factory()
        );
    }
}

