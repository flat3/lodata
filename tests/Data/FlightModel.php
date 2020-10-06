<?php

namespace Flat3\OData\Tests\Data;

use Exception;
use Flat3\OData\Controller\Transaction;
use Flat3\OData\DeclaredProperty;
use Flat3\OData\Drivers\SQLEntitySet;
use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\Model;
use Flat3\OData\NavigationBinding;
use Flat3\OData\NavigationProperty;
use Flat3\OData\PrimitiveType;
use Flat3\OData\ReferentialConstraint;
use Flat3\OData\Tests\Models\Airport as AirportEModel;
use Flat3\OData\Tests\Models\Flight as FlightEModel;
use Flat3\OData\Type\Decimal;
use Flat3\OData\Type\Int32;
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

        (new AirportEModel([
            'code' => 'ohr',
            'name' => "O'Hare",
            'construction_date' => '1930-01-01',
            'open_time' => '15:00:00',
            'sam_datetime' => '1999-11-10T14:00:01+00:00',
            'is_big' => true,
        ]))->save();

        try {
            $flightType = Model::entitytype('flight');
            $flightType->setKey(DeclaredProperty::factory('id', PrimitiveType::int32()));
            $flightType->addProperty(DeclaredProperty::factory('origin', PrimitiveType::string()));
            $flightType->addProperty(DeclaredProperty::factory('destination', PrimitiveType::string()));
            $flightType->addProperty(DeclaredProperty::factory('gate', PrimitiveType::int32()));
            $flightSet = new SQLEntitySet('flights', $flightType);
            $flightSet->setTable('flights');

            $airportType = Model::entitytype('airport');
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

            Model::fn('exf1')
                ->setCallback(function (): String_ {
                    return String_::factory('hello');
                });

            Model::fn('exf2')
                ->setCallback(function (EntitySet $airports): EntitySet {
                    $airport = new Airport();
                    $airport['code'] = 'xyz';

                    return $airports;
                })
                ->setType($airportType);

            Model::fn('exf3')
                ->setCallback(function (String_ $code): Entity {
                    /** @var Model $model */
                    $model = app()->get(Model::class);
                    $airport = new Airport();
                    $airport->setType($model->getEntityTypes()->get('airport'));
                    $airport['code'] = $code->get();
                    return $airport;
                })
                ->setType($airportType);

            Model::action('exa1')
                ->setCallback(function (): String_ {
                    return String_::factory('hello');
                });

            Model::fn('add')
                ->setCallback(function (Int32 $a, Int32 $b): Int32 {
                    return Int32::factory($a->get() + $b->get());
                });

            Model::fn('div')
                ->setCallback(function (Int32 $a, Int32 $b): Decimal {
                    return Decimal::factory($a->get() / $b->get());
                });

            Model::fn('ffn1')
                ->setCallback(function (Transaction $transaction, EntitySet $flights): EntitySet {
                    $transaction->getSelect()->setValue('origin');
                    return $flights;
                })
                ->setType(Model::getType('flight'));
        } catch (Exception $e) {
        }
    }
}
