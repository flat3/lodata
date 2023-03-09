<?php

namespace Flat3\Lodata\Tests\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Laravel\Models\Flight;
use Flat3\Lodata\Tests\Laravel\Models\Passenger;
use Flat3\Lodata\Tests\Laravel\Models\Pet;
use Flat3\Lodata\Tests\Laravel\Services\Instance;
use Flat3\Lodata\Tests\Laravel\Services\Service;
use Flat3\Lodata\Tests\TestCase;

/**
 * @requires PHP >= 8.1
 */
class DiscoveryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Lodata::discover(Flight::class);
        Lodata::discover(Service::class);
        Lodata::discover(Passenger::class);
        Lodata::discover(Pet::class);

        Lodata::discover(Passenger::class);
        $passengers = Lodata::getEntitySet('Passengers');
        $passengerType = $passengers->getType();

        if (!Lodata::getTypeDefinition('colour')) {
            $this->addEnumerationTypes();
            $passengerType->addDeclaredProperty('colour', Lodata::getTypeDefinition('Colours'));
            $passengerType->addDeclaredProperty('sock_colours', Lodata::getTypeDefinition('MultiColours'));
        }

        $pets = Lodata::getEntitySet('Pets');

        $instance = new Instance();
        $instance->a = 'c';
        Lodata::discover($instance);
    }

    public function test_metadata()
    {
        $this->assertMetadataSnapshot();
    }

    public function test_simple()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/hello()')
        );
    }

    public function test_identity()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/identity(arg='hello')")
        );
    }

    public function test_bind()
    {
        $this->assertJsonResponseSnapshot(
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
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/insarg()')
        );
    }
}