<?php

namespace Flat3\OData\Tests\Data;

use Exception;
use Flat3\OData\DataModel;
use Flat3\OData\Drivers\Database\Store;
use Flat3\OData\Entity;
use Flat3\OData\Operation\Action;
use Flat3\OData\Operation\Function_;
use Flat3\OData\Property;
use Flat3\OData\Tests\Models\Airport;
use Flat3\OData\Tests\Models\Flight;
use Flat3\OData\Type;
use Flat3\OData\Type\EntityType;
use Flat3\OData\Type\String_;

trait FlightDataModel
{
    public function withFlightDataModel(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->artisan('migrate')->run();

        (new Flight([
            'origin' => 'lhr',
            'destination' => 'lax',
        ]))->save();

        (new Flight([
            'origin' => 'sam',
            'destination' => 'rgr',
        ]))->save();

        (new Airport([
            'code' => 'lhr',
            'name' => 'Heathrow',
            'construction_date' => '1946-03-25',
            'open_time' => '09:00:00',
            'sam_datetime' => '2001-11-10T14:00:00+00:00',
            'is_big' => true,
        ]))->save();

        (new Airport([
            'code' => 'lax',
            'name' => 'Los Angeles',
            'construction_date' => '1930-01-01',
            'open_time' => '08:00:00',
            'sam_datetime' => '2000-11-10T14:00:00+00:00',
            'is_big' => false,
        ]))->save();

        (new Airport([
            'code' => 'sfo',
            'name' => 'San Francisco',
            'construction_date' => '1930-01-01',
            'open_time' => '15:00:00',
            'sam_datetime' => '2001-11-10T14:00:01+00:00',
            'is_big' => null,
        ]))->save();

        try {
            /** @var DataModel $model */
            $model = app()->make(DataModel::class);

            $flightType = new EntityType('flight');
            $flightType->setKey(new Property\Declared('id', Type::int32()));
            $flightType->addProperty(new Property\Declared('origin', Type::string()));
            $flightType->addProperty(new Property\Declared('destination', Type::string()));
            $flightType->addProperty(new Property\Declared('gate', Type::int32()));
            $flightStore = new Store('flights', $flightType);
            $flightStore->setTable('flights');

            $airportType = new EntityType('airport');
            $airportType->setKey(new Property\Declared('id', Type::int32()));
            $airportType->addProperty(new Property\Declared('name', Type::string()));
            $airportType->addProperty((new Property\Declared('code', Type::string()))->setSearchable());
            $airportType->addProperty(new Property\Declared('construction_date', Type::date()));
            $airportType->addProperty(new Property\Declared('open_time', Type::timeofday()));
            $airportType->addProperty(new Property\Declared('sam_datetime', Type::datetimeoffset()));
            $airportType->addProperty(new Property\Declared('review_score', Type::decimal()));
            $airportType->addProperty(new Property\Declared('is_big', Type::boolean()));
            $airportStore = new Store('airports', $airportType);
            $airportStore->setTable('airports');

            $model
                ->add($flightType)
                ->add($flightStore);

            $model
                ->add($airportType)
                ->add($airportStore);

            $nav = new Property\Navigation($airportStore, $airportType);
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
            $flightStore->addNavigationBinding(new Property\Navigation\Binding($nav, $airportStore));

            $exf1 = new Function_('exf1');
            $exf1->setCallback(function (): String_ {
                return String_::factory('hello');
            });

            $exa1 = new Action('exa1');
            $exa1->setCallback(function (): String_ {
                return String_::factory('hello');
            });

            $add = Function_::factory('add', Type::int32())
                ->setCallback(function (Type\Int32 $a, Type\Int32 $b): Type\Int32 {
                    return Type\Int32::factory($a->getInternalValue() + $b->getInternalValue());
                });

            $model->add($add);
            $model->add($exf1);
            $model->add($exa1);
        } catch (Exception $e) {
        }
    }
}
