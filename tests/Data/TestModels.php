<?php

namespace Flat3\Lodata\Tests\Data;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Interfaces\FunctionInterface;
use Flat3\Lodata\Interfaces\QueryInterface;
use Flat3\Lodata\Model;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Tests\Models\Airport as AirportEModel;
use Flat3\Lodata\Tests\Models\Flight as FlightEModel;
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
        $flightType = Model::add(new EntityType('flight'));
        $flightType->setKey(DeclaredProperty::factory('id', PrimitiveType::int32()));
        $flightType->addProperty(DeclaredProperty::factory('origin', PrimitiveType::string()));
        $flightType->addProperty(DeclaredProperty::factory('destination', PrimitiveType::string()));
        $flightType->addProperty(DeclaredProperty::factory('gate', PrimitiveType::int32()));
        $flightSet = new SQLEntitySet('flights', $flightType);
        $flightSet->setTable('flights');

        /** @var EntityType $airportType */
        $airportType = Model::add(new EntityType('airport'));
        $airportType->setKey(DeclaredProperty::factory('id', PrimitiveType::int32()));
        $airportType->addProperty(DeclaredProperty::factory('name', PrimitiveType::string()));
        $airportType->addProperty(DeclaredProperty::factory('code', PrimitiveType::string())->setSearchable());
        $airportType->addProperty(DeclaredProperty::factory('construction_date', PrimitiveType::date()));
        $airportType->addProperty(DeclaredProperty::factory('open_time', PrimitiveType::timeofday()));
        $airportType->addProperty(DeclaredProperty::factory('sam_datetime', PrimitiveType::datetimeoffset()));
        $airportType->addProperty(DeclaredProperty::factory('review_score', PrimitiveType::decimal()));
        $airportType->addProperty(DeclaredProperty::factory('is_big', PrimitiveType::boolean()));
        $airportSet = new SQLEntitySet('airports', $airportType);
        $airportSet->setTable('airports');

        Model::add($flightSet);
        Model::add($airportSet);

        $nav = new NavigationProperty($airportSet, $airportType);
        $nav->setCollection(true);
        $nav->addConstraint(
            new ReferentialConstraint(
                $flightType->getProperty('origin'),
                $airportType->getProperty('code')
            )
        );
        $nav->addConstraint(
            new ReferentialConstraint(
                $flightType->getProperty('destination'),
                $airportType->getProperty('code')
            )
        );
        $flightType->addProperty($nav);
        $flightSet->addNavigationBinding(new NavigationBinding($nav, $airportSet));
    }

    public function withMathFunctions()
    {
        Model::add(new class('add') extends Operation implements FunctionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return Int32::factory($a->get() + $b->get());
            }
        });

        Model::add(new class('div') extends Operation implements FunctionInterface {
            public function invoke(Int32 $a, Int32 $b): Decimal
            {
                return Decimal::factory($a->get() / $b->get());
            }
        });
    }

    public function withTextModel()
    {
        Model::add(
            new class(
                'texts',
                Model::add(new EntityType('text'))
                    ->addProperty(DeclaredProperty::factory('a', PrimitiveType::string()))
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
