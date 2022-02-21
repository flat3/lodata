<?php

namespace Flat3\Lodata\Tests\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Laravel\Services\Math;
use Flat3\Lodata\Tests\TestCase;

/**
 * @requires PHP >=8
 */
class NamespaceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Lodata::discover(Math::class);
    }

    public function test_metadata()
    {
        $this->assertMetadataSnapshot();
    }

    public function test_add()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/com.example.math.add(a=1,b=2)')
        );
    }
}