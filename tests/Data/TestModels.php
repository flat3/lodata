<?php

namespace Flat3\Lodata\Tests\Data;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\DynamicProperty;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Models\Airport as AirportEModel;
use Flat3\Lodata\Tests\Models\Flight as FlightEModel;
use Flat3\Lodata\Tests\Models\Passenger as PassengerEModel;
use Flat3\Lodata\Tests\Models\Pet as PetEModel;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Int32;
use Generator;

trait TestModels
{
    public function withFlightData(): void
    {
        (new FlightEModel([
            'origin' => 'lhr',
            'destination' => 'lax',
            'duration' => 41100,
        ]))->save();

        (new FlightEModel([
            'origin' => 'sam',
            'destination' => 'rgr',
            'duration' => 2384,
        ]))->save();

        (new FlightEModel([
            'origin' => 'sfo',
            'destination' => 'lax',
            'duration' => 2133,
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

    public function withFlightDataV2(): void
    {
        (new PetEModel([
            'name' => 'Alice',
        ]))->save();

        (new PetEModel([
            'name' => 'Bob',
        ]))->save();
    }

    public function withSingleton(): void
    {
        $type = new EntityType('sType');
        $type->addProperty(new DeclaredProperty('name', Type::string()));
        $singleton = new Singleton('sInstance', $type);
        $singleton['name'] = 'Bob';

        Lodata::add($singleton);
    }

    public function withFlightModel(): void
    {
        $this->withFlightData();

        /** @var EntityType $passengerType */
        $passengerType = Lodata::add(
            (new EntityType('passenger'))
                ->setKey(
                    (new DeclaredProperty('id', Type::int32()))
                        ->addAnnotation(new Computed)
                )
                ->addProperty((new DeclaredProperty('name', Type::string()))->setSearchable()->setNullable(false))
                ->addDeclaredProperty('flight_id', Type::int32())
        );

        $passengerSet = (new SQLEntitySet('passengers', $passengerType))
            ->setTable('passengers');

        /** @var EntityType $flightType */
        $flightType = Lodata::add(
            (new EntityType('flight'))
                ->setKey(
                    (new DeclaredProperty('id', Type::int32()))
                        ->addAnnotation(new Computed)
                )
                ->addDeclaredProperty('origin', Type::string())
                ->addDeclaredProperty('destination', Type::string())
                ->addDeclaredProperty('gate', Type::int32())
                ->addDeclaredProperty('duration', Type::duration())
        );

        $flightSet = (new SQLEntitySet('flights', $flightType))
            ->setTable('flights');

        /** @var EntityType $airportType */
        $airportType = Lodata::add(
            (new EntityType('airport'))
                ->setKey(
                    (new DeclaredProperty('id', Type::int32()))
                        ->addAnnotation(new Computed)
                )
                ->addProperty((new DeclaredProperty('name', Type::string()))->setNullable(false))
                ->addProperty((new DeclaredProperty('code', Type::string()))->setSearchable()->setNullable(false))
                ->addDeclaredProperty('construction_date', Type::date())
                ->addDeclaredProperty('open_time', Type::timeofday())
                ->addDeclaredProperty('sam_datetime', Type::datetimeoffset())
                ->addDeclaredProperty('review_score', Type::decimal())
                ->addDeclaredProperty('is_big', Type::boolean())
        );

        $airportSet = (new SQLEntitySet('airports', $airportType))
            ->setTable('airports');

        /** @var EntityType $petType */
        $petType = Lodata::add(
            (new EntityType('pet'))
                ->setKey(
                    (new DeclaredProperty('id', Type::int32()))
                        ->addAnnotation(new Computed)
                )
                ->addDeclaredProperty('name', Type::string())
                ->addDeclaredProperty('type', Type::string())
                ->addDeclaredProperty('passenger_id', Type::int32())
        );

        $petSet = (new SQLEntitySet('pets', $petType))
            ->setTable('pets');

        Lodata::add($passengerSet);
        Lodata::add($flightSet);
        Lodata::add($airportSet);
        Lodata::add($petSet);

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

        $flightToPassenger = (new NavigationProperty($passengerSet, $passengerType))
            ->setCollection(true)
            ->addConstraint($passengerFlight);

        $binding = new NavigationBinding($flightToPassenger, $passengerSet);

        $flightType->addProperty($flightToPassenger);
        $flightSet->addNavigationBinding($binding);

        $petPassenger = new ReferentialConstraint(
            $passengerType->getProperty('id'),
            $petType->getProperty('passenger_id')
        );

        $passengerToPets = (new NavigationProperty($petSet, $petType))
            ->setCollection(true)
            ->addConstraint($petPassenger);
        $binding = new NavigationBinding($passengerToPets, $petSet);
        $passengerType->addProperty($passengerToPets);
        $passengerSet->addNavigationBinding($binding);
    }

    public function withMathFunctions()
    {
        $add = new Operation\Function_('add');
        $add->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($add);

        $div = new Operation\Function_('div');
        $div->setCallable(function (Int32 $a, Int32 $b): Decimal {
            return new Decimal($a->get() / $b->get());
        });
        Lodata::add($div);
    }

    public function withTextModel()
    {
        Lodata::add(
            new class(
                'texts',
                Lodata::add((new EntityType('text'))
                    ->addDeclaredProperty('a', Type::string()))
            ) extends EntitySet implements QueryInterface {
                public function query(): Generator
                {
                    $entity = $this->newEntity();
                    $entity['a'] = 'a';
                    yield $entity;
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
                public function query(): Generator
                {
                    $entity = $this->newEntity();
                    $entity['declared'] = 'a';
                    $pv = $entity->newPropertyValue();
                    $pv->setValue(new Int32(3));
                    $pv->setProperty(new DynamicProperty('dynamic', Type::int32()));
                    $entity->addPropertyValue($pv);
                    yield $entity;
                }
            });
    }
}
