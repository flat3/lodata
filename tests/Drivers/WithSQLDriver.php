<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Annotation\Core\V1\Description;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\JSON;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\DB;

trait WithSQLDriver
{
    protected function setUpDriver(): void
    {
        $this->entityId = 1;
        $this->missingEntityId = 99;

        /** @var EntityType $passengerType */
        $passengerType = Lodata::add(
            (new EntityType('passenger'))
                ->setKey(
                    (new DeclaredProperty('id', Type::int32()))
                        ->addAnnotation(new Computed)
                )
        );
        $this->addPassengerProperties($passengerType);
        $passengerType->getDeclaredProperty('name')->setSearchable()->setNullable(false);

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
                ->addProperty((new DeclaredProperty('code',
                    Type::string()))->setAlternativeKey()->setSearchable()->setNullable(false))
                ->addDeclaredProperty('construction_date', Type::date())
                ->addDeclaredProperty('open_time', Type::timeofday())
                ->addDeclaredProperty('sam_datetime', Type::datetimeoffset())
                ->addDeclaredProperty('review_score', Type::decimal())
                ->addDeclaredProperty('is_big', Type::boolean())
                ->addDeclaredProperty('country_id', Type::int32())
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

        /** @var EntityType $countryType */
        $countryType = Lodata::add(
            (new EntityType('country'))
                ->setKey(
                    (new DeclaredProperty('id', Type::int32()))
                        ->addAnnotation(new Computed)
                )
                ->addDeclaredProperty('name', Type::string())
        );

        $countrySet = (new SQLEntitySet('countries', $countryType))
            ->setTable('countries');

        Lodata::add($passengerSet);
        Lodata::add($flightSet);
        Lodata::add($airportSet);
        Lodata::add($petSet);
        Lodata::add($countrySet);

        // Flight -> Airports

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

        $toOrigin = (new NavigationProperty('originAirport', $airportType))
            ->setCollection(false)
            ->addConstraint($originCode);
        $binding = new NavigationBinding($toOrigin, $airportSet);
        $flightType->addProperty($toOrigin);
        $flightSet->addNavigationBinding($binding);

        $toDestination = (new NavigationProperty('destinationAirport', $airportType))
            ->setCollection(false)
            ->addConstraint($originCode);
        $binding = new NavigationBinding($toDestination, $airportSet);
        $flightType->addProperty($toDestination);
        $flightSet->addNavigationBinding($binding);

        // Flight -> Passenger

        $flightPassenger = new ReferentialConstraint(
            $flightType->getProperty('id'),
            $passengerType->getProperty('flight_id')
        );

        $flightToPassenger = (new NavigationProperty($passengerSet, $passengerType))
            ->setCollection(true)
            ->addConstraint($flightPassenger);
        $binding = new NavigationBinding($flightToPassenger, $passengerSet);
        $flightType->addProperty($flightToPassenger);
        $flightSet->addNavigationBinding($binding);

        // Passenger -> Flight

        $passengerFlight = new ReferentialConstraint(
            $passengerType->getProperty('flight_id'),
            $flightType->getProperty('id')
        );

        $passengerToFlight = (new NavigationProperty('flight', $flightType))
            ->setCollection(false)
            ->addConstraint($passengerFlight);
        $binding = new NavigationBinding($passengerToFlight, $flightSet);
        $passengerType->addProperty($passengerToFlight);
        $passengerSet->addNavigationBinding($binding);

        // Pet -> Passenger

        $petPassenger = new ReferentialConstraint(
            $passengerType->getProperty('id'),
            $petType->getProperty('passenger_id')
        );

        $passengerToPets = (new NavigationProperty('MyPets', $petType))
            ->setCollection(true)
            ->addConstraint($petPassenger)
            ->addAnnotation(new Description('All my pets'));
        $binding = new NavigationBinding($passengerToPets, $petSet);
        $passengerType->addProperty($passengerToPets);
        $passengerSet->addNavigationBinding($binding);
        $passengerSet->setPropertySourceName($passengerToPets, 'pets');

        // Airport -> Country

        $airportCountry = new ReferentialConstraint(
            $airportType->getProperty('country_id'),
            $countryType->getProperty('id')
        );

        $airportToCountry = (new NavigationProperty('country', $countryType))
            ->setCollection(false)
            ->addConstraint($airportCountry);
        $binding = new NavigationBinding($airportToCountry, $countrySet);
        $airportType->addProperty($airportToCountry);
        $airportSet->addNavigationBinding($binding);

        // Airport -> Flights

        $originCode = new ReferentialConstraint(
            $airportType->getProperty('code'),
            $flightType->getProperty('origin')
        );

        $destinationCode = new ReferentialConstraint(
            $airportType->getProperty('code'),
            $flightType->getProperty('destination')
        );

        $airportToFlights = (new NavigationProperty($flightSet, $flightType))
            ->setCollection(true)
            ->addConstraint($originCode)
            ->addConstraint($destinationCode);
        $binding = new NavigationBinding($airportToFlights, $flightSet);
        $airportType->addProperty($airportToFlights);
        $airportSet->addNavigationBinding($binding);

        $encodeArrays = function (array $record): array {
            return array_map(function ($value) {
                return is_array($value) ? JSON::encode($value) : $value;
            }, $record);
        };

        foreach ($this->getSeed() as $record) {
            DB::table('passengers')->insert($encodeArrays($record));
        }

        foreach ($this->getAirportSeed() as $record) {
            DB::table('airports')->insert($encodeArrays($record));
        }

        foreach ($this->getFlightSeed() as $record) {
            DB::table('flights')->insert($encodeArrays($record));
        }

        foreach ($this->getCountrySeed() as $record) {
            DB::table('countries')->insert($encodeArrays($record));
        }

        foreach ($this->getPetSeed() as $record) {
            DB::table('pets')->insert($encodeArrays($record));
        }

        $this->keepDriverState();
        $this->updateETag();
    }

    protected function tearDownDriver(): void
    {
        $this->assertDriverStateDiffSnapshot();
    }

    protected function captureDriverState(): array
    {
        return $this->captureDatabase();
    }
}