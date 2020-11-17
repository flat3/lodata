<?php

namespace Flat3\Lodata\Tests\Data;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\DynamicProperty;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Tests\Models\Airport as AirportEModel;
use Flat3\Lodata\Tests\Models\Flight as FlightEModel;
use Flat3\Lodata\Tests\Models\Passenger as PassengerEModel;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Int32;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait TestModels
{
    public function withFlightDatabase(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->integer('gate')->nullable();
        });

        Schema::create('airports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code');
            $table->date('construction_date')->nullable();
            $table->dateTime('sam_datetime')->nullable();
            $table->time('open_time')->nullable();
            $table->float('review_score')->nullable();
            $table->boolean('is_big')->nullable();
            $table->bigInteger('country_id')->nullable();
        });

        Schema::create('passengers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('flight_id');
            $table->string('name');
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });
    }

    public function withFlightData(): void
    {
        (new FlightEModel([
            'origin' => 'lhr',
            'destination' => 'lax',
        ]))->save();

        (new FlightEModel([
            'origin' => 'sam',
            'destination' => 'rgr',
        ]))->save();

        (new FlightEModel([
            'origin' => 'sfo',
            'destination' => 'lax',
        ]))->save();

        (new PassengerEModel([
            'name' => 'Anne Arbor',
            'flight_id' => 1,
        ]))->save();

        (new PassengerEModel([
            'name' => 'Bob Barry',
            'flight_id' => 1,
        ]))->save();

        (new PassengerEModel([
            'name' => 'Charlie Carrot',
            'flight_id' => 1,
        ]))->save();

        (new PassengerEModel([
            'name' => 'Fox Flipper',
            'flight_id' => 2,
        ]))->save();

        (new PassengerEModel([
            'name' => 'Grace Gumbo',
            'flight_id' => 3,
        ]))->save();

        (new AirportEModel([
            'code' => 'lhr',
            'name' => 'Heathrow',
            'construction_date' => '1946-03-25',
            'open_time' => '09:00:00',
            'sam_datetime' => '2001-11-10T14:00:00+00:00',
            'is_big' => true,
        ]))->save();

        (new AirportEModel([
            'code' => 'lax',
            'name' => 'Los Angeles',
            'construction_date' => '1930-01-01',
            'open_time' => '08:00:00',
            'sam_datetime' => '2000-11-10T14:00:00+00:00',
            'is_big' => false,
        ]))->save();

        (new AirportEModel([
            'code' => 'sfo',
            'name' => 'San Francisco',
            'construction_date' => '1930-01-01',
            'open_time' => '15:00:00',
            'sam_datetime' => '2001-11-10T14:00:01+00:00',
            'is_big' => null,
        ]))->save();

        (new AirportEModel([
            'code' => 'ohr',
            'name' => "O'Hare",
            'construction_date' => '1930-01-01',
            'open_time' => '15:00:00',
            'sam_datetime' => '1999-11-10T14:00:01+00:00',
            'is_big' => true,
        ]))->save();
    }

    public function withFlightModel(): void
    {
        $this->withFlightDatabase();
        $this->withFlightData();

        /** @var EntityType $passengerType */
        $passengerType = Lodata::add(
            EntityType::factory('passenger')
                ->setKey(new DeclaredProperty('id', Type::int32()))
                ->addProperty((new DeclaredProperty('name', Type::string()))->setSearchable())
                ->addDeclaredProperty('flight_id', Type::int32())
        );

        $passengerSet = SQLEntitySet::factory('passengers', $passengerType)
            ->setTable('passengers');

        /** @var EntityType $flightType */
        $flightType = Lodata::add(
            EntityType::factory('flight')
                ->setKey(new DeclaredProperty('id', Type::int32()))
                ->addDeclaredProperty('origin', Type::string())
                ->addDeclaredProperty('destination', Type::string())
                ->addDeclaredProperty('gate', Type::int32())
        );

        $flightSet = SQLEntitySet::factory('flights', $flightType)
            ->setTable('flights');

        /** @var EntityType $airportType */
        $airportType = Lodata::add(
            EntityType::factory('airport')
                ->setKey(new DeclaredProperty('id', Type::int32()))
                ->addDeclaredProperty('name', Type::string())
                ->addProperty((new DeclaredProperty('code', Type::string()))->setSearchable())
                ->addDeclaredProperty('construction_date', Type::date())
                ->addDeclaredProperty('open_time', Type::timeofday())
                ->addDeclaredProperty('sam_datetime', Type::datetimeoffset())
                ->addDeclaredProperty('review_score', Type::decimal())
                ->addDeclaredProperty('is_big', Type::boolean())
        );

        $airportSet = SQLEntitySet::factory('airports', $airportType)
            ->setTable('airports');

        Lodata::add($passengerSet);
        Lodata::add($flightSet);
        Lodata::add($airportSet);

        $originCode = new ReferentialConstraint(
            $flightType->getProperty('origin'),
            $airportType->getProperty('code')
        );

        $destinationCode = new ReferentialConstraint(
            $flightType->getProperty('destination'),
            $airportType->getProperty('code')
        );

        $toAirport = (new NavigationProperty($airportSet, $airportType))
            ->setCollection(true)
            ->addConstraint($originCode)
            ->addConstraint($destinationCode);

        $binding = new NavigationBinding($toAirport, $airportSet);

        $flightType->addProperty($toAirport);
        $flightSet->addNavigationBinding($binding);

        $passengerFlight = new ReferentialConstraint(
            $flightType->getProperty('id'),
            $passengerType->getProperty('flight_id')
        );

        $toPassenger = (new NavigationProperty($passengerSet, $passengerType))
            ->setCollection(true)
            ->addConstraint($passengerFlight);

        $binding = new NavigationBinding($toPassenger, $passengerSet);

        $flightType->addProperty($toPassenger);
        $flightSet->addNavigationBinding($binding);
    }

    public function withMathFunctions()
    {
        Lodata::add(new class('add') extends Operation implements FunctionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return Int32::factory($a->get() + $b->get());
            }
        });

        Lodata::add(new class('div') extends Operation implements FunctionInterface {
            public function invoke(Int32 $a, Int32 $b): Decimal
            {
                return Decimal::factory($a->get() / $b->get());
            }
        });
    }

    public function withTextModel()
    {
        Lodata::add(
            new class(
                'texts',
                Lodata::add((new EntityType('text'))
                    ->addDeclaredProperty('a', Type::string()))
            ) extends EntitySet implements QueryInterface {
                public function query(): array
                {
                    $entity = $this->newEntity();
                    $entity['a'] = 'a';
                    return [
                        $entity,
                    ];
                }
            });
    }

    public function withDynamicPropertyModel()
    {
        Lodata::add(
            new class(
                'example',
                Lodata::add((new EntityType('text'))
                    ->addDeclaredProperty('declared', Type::string()))
            ) extends EntitySet implements QueryInterface {
                public function query(): array
                {
                    $entity = $this->newEntity();
                    $entity['declared'] = 'a';
                    $pv = $entity->newPropertyValue();
                    $pv->setValue(new Int32(3));
                    $pv->setProperty(new DynamicProperty('dynamic', Type::int32()));
                    $entity->addProperty($pv);
                    return [
                        $entity,
                    ];
                }
            });
    }
}
