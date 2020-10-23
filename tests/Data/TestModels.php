<?php

namespace Flat3\Lodata\Tests\Data;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\FunctionInterface;
use Flat3\Lodata\Interfaces\QueryInterface;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Tests\Models\Airport as AirportEModel;
use Flat3\Lodata\Tests\Models\Flight as FlightEModel;
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
        });
    }

    public function withFlightModel(): void
    {
        $this->withFlightDatabase();

        (new FlightEModel([
            'origin' => 'lhr',
            'destination' => 'lax',
        ]))->save();

        (new FlightEModel([
            'origin' => 'sam',
            'destination' => 'rgr',
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

        /** @var EntityType $flightType */
        $flightType = Lodata::add(new EntityType('flight'));
        $flightType->setKey(new DeclaredProperty('id', Type::int32()));
        $flightType->addProperty(new DeclaredProperty('origin', Type::string()));
        $flightType->addProperty(new DeclaredProperty('destination', Type::string()));
        $flightType->addProperty(new DeclaredProperty('gate', Type::int32()));
        $flightSet = new SQLEntitySet('flights', $flightType);
        $flightSet->setTable('flights');

        /** @var EntityType $airportType */
        $airportType = Lodata::add(new EntityType('airport'));
        $airportType->setKey(new DeclaredProperty('id', Type::int32()));
        $airportType->addProperty(new DeclaredProperty('name', Type::string()));
        $airportType->addProperty((new DeclaredProperty('code', Type::string()))->setSearchable());
        $airportType->addProperty(new DeclaredProperty('construction_date', Type::date()));
        $airportType->addProperty(new DeclaredProperty('open_time', Type::timeofday()));
        $airportType->addProperty(new DeclaredProperty('sam_datetime', Type::datetimeoffset()));
        $airportType->addProperty(new DeclaredProperty('review_score', Type::decimal()));
        $airportType->addProperty(new DeclaredProperty('is_big', Type::boolean()));
        $airportSet = new SQLEntitySet('airports', $airportType);
        $airportSet->setTable('airports');

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
                Lodata::add(new EntityType('text'))
                    ->addProperty(new DeclaredProperty('a', Type::string()))
            ) extends EntitySet implements QueryInterface {
                public function query(): array
                {
                    return [
                        $this->newEntity()
                            ->setPrimitive('a', 'a')
                    ];
                }
            });
    }
}
