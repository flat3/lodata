<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Operations\Math;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class NamespaceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped();
        }

        Lodata::discover(Math::class);
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_add()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/com.example.math.add(a=1,b=2)')
        );
    }
}