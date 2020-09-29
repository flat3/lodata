<?php

namespace Flat3\OData\Tests\Data;

use Exception;
use Flat3\OData\DataModel;
use Flat3\OData\Drivers\Database\Store;
use Flat3\OData\EntityType\Collection;
use Flat3\OData\Property;
use Flat3\OData\Tests\Models\Airport;
use Flat3\OData\Tests\Models\Flight;
use Flat3\OData\Type\Boolean;
use Flat3\OData\Type\Date;
use Flat3\OData\Type\DateTimeOffset;
use Flat3\OData\Type\Decimal;
use Flat3\OData\Type\Int32;
use Flat3\OData\Type\String_;
use Flat3\OData\Type\TimeOfDay;

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

            $flightType = new Collection('flight');
            $flightType->setKey(new Property\Declared('id', Int32::class));
            $flightType->addProperty(new Property\Declared('origin', String_::class));
            $flightType->addProperty(new Property\Declared('destination', String_::class));
            $flightType->addProperty(new Property\Declared('gate', Int32::class));
            $flightStore = new Store('flights', $flightType);
            $flightStore->setTable('flights');

            $airportType = new Collection('airport');
            $airportType->setKey(new Property\Declared('id', Int32::class));
            $airportType->addProperty(new Property\Declared('name', String_::class));
            $airportType->addProperty((new Property\Declared('code', String_::class))->setSearchable());
            $airportType->addProperty(new Property\Declared('construction_date', Date::class));
            $airportType->addProperty(new Property\Declared('open_time', TimeOfDay::class));
            $airportType->addProperty(new Property\Declared('sam_datetime', DateTimeOffset::class));
            $airportType->addProperty(new Property\Declared('review_score', Decimal::class));
            $airportType->addProperty(new Property\Declared('is_big', Boolean::class));
            $airportStore = new Store('airports', $airportType);
            $airportStore->setTable('airports');

            $model
                ->addEntityType($flightType)
                ->addResource($flightStore);

            $model
                ->addEntityType($airportType)
                ->addResource($airportStore);

            $nav = new Property\Navigation($airportStore, $airportType);
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
        } catch (Exception $e) {
        }
    }
}
