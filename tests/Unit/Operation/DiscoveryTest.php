<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Flight;
use Flat3\Lodata\Tests\Models\Passenger;
use Flat3\Lodata\Tests\Models\Pet;
use Flat3\Lodata\Tests\Operations\Instance;
use Flat3\Lodata\Tests\Operations\Service;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class DiscoveryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped();
        }

        $this->withFlightDatabase();

        Lodata::discover(Flight::class);
        Lodata::discover(Service::class);
        Lodata::discover(Passenger::class);
        Lodata::discover(Pet::class);

        $instance = new Instance();
        $instance->a = 'c';
        Lodata::discover($instance);
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_simple()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/hello()')
        );
    }

    public function test_identity()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/identity(arg='hello')")
        );
    }

    public function test_bind()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/add(a=1,b=1)/increment")
        );
    }

    public function test_action()
    {
        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/exec()')
        );
    }

    public function test_new_name()
    {
        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/exec2()')
        );
    }

    public function test_instance()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/insarg()')
        );
    }
}