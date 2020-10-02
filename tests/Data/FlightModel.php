<?php

namespace Flat3\OData\Tests\Data;

use Exception;
use Flat3\OData\Drivers\Database\EntitySet;
use Flat3\OData\Entity;
use Flat3\OData\EntitySet\Dynamic;
use Flat3\OData\ODataModel;
use Flat3\OData\Property;
use Flat3\OData\Tests\Models\Airport as AirportEModel;
use Flat3\OData\Tests\Models\Flight as FlightEModel;
use Flat3\OData\Type;
use Flat3\OData\Type\String_;

trait FlightModel
{
    public function withFlightModel(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->artisan('migrate')->run();

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

        try {
            $flightType = ODataModel::entitytype('flight');
            $flightType->setKey(new Property\Declared('id', Type::int32()));
            $flightType->addProperty(new Property\Declared('origin', Type::string()));
            $flightType->addProperty(new Property\Declared('destination', Type::string()));
            $flightType->addProperty(new Property\Declared('gate', Type::int32()));
            $flightSet = new EntitySet('flights', $flightType);
            $flightSet->setTable('flights');

            $airportType = ODataModel::entitytype('airport');
            $airportType->setKey(new Property\Declared('id', Type::int32()));
            $airportType->addProperty(new Property\Declared('name', Type::string()));
            $airportType->addProperty(Property\Declared::factory('code', Type::string())->setSearchable());
            $airportType->addProperty(new Property\Declared('construction_date', Type::date()));
            $airportType->addProperty(new Property\Declared('open_time', Type::timeofday()));
            $airportType->addProperty(new Property\Declared('sam_datetime', Type::datetimeoffset()));
            $airportType->addProperty(new Property\Declared('review_score', Type::decimal()));
            $airportType->addProperty(new Property\Declared('is_big', Type::boolean()));
            $airportSet = new EntitySet('airports', $airportType);
            $airportSet->setTable('airports');

            ODataModel::add($flightSet);
            ODataModel::add($airportSet);

            $nav = new Property\Navigation($airportSet, $airportType);
            $nav->setCollection(true);
            $nav->addConstraint(
                new Property\Constraint(
                    $flightType->getProperty('origin'),
                    $airportType->getProperty('code')
                )
            );
            $nav->addConstraint(
                new Property\Constraint(
                    $flightType->getProperty('destination'),
                    $airportType->getProperty('code')
                )
            );
            $flightType->addProperty($nav);
            $flightSet->addNavigationBinding(new Property\Navigation\Binding($nav, $airportSet));

            ODataModel::fn('exf1')
                ->setCallback(function (): String_ {
                    return String_::factory('hello');
                });

            ODataModel::fn('exf2')
                ->setCallback(function (): \Flat3\OData\Resource\EntitySet {
                    $type = ODataModel::getType('airport');
                    $airports = ODataModel::getResource('airports');
                    $airport = new Airport();
                    $airport['code'] = 'xyz';
                    $set = new Dynamic($airports, $type);
                    $set->addResult($airport);
                    return $set;
                })
                ->setType($airportType);

            ODataModel::fn('exf3')
                ->setCallback(function (String_ $code): Entity {
                    $airport = new Airport();
                    $airport['code'] = $code->get();
                    return $airport;
                })
                ->setType($airportType);

            ODataModel::action('exa1')
                ->setCallback(function (): String_ {
                    return String_::factory('hello');
                });

            ODataModel::fn('add')
                ->setCallback(function (Type\Int32 $a, Type\Int32 $b): Type\Int32 {
                    return Type\Int32::factory($a->get() + $b->get());
                })
                ->setType(Type::int32());
        } catch (Exception $e) {
        }
    }
}
