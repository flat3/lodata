<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation\Function_;
use Flat3\Lodata\Tests\Laravel\Models\Airport;
use Flat3\Lodata\Tests\Laravel\Models\Country;
use Flat3\Lodata\Tests\Laravel\Models\Flight;
use Flat3\Lodata\Tests\Laravel\Models\Passenger;
use Flat3\Lodata\Tests\Laravel\Models\Pet;
use Flat3\Lodata\Type;

trait WithEloquentDriver
{
    protected function setUpDriver(): void
    {
        $this->entitySet = 'Passengers';
        $this->airportEntitySet = 'Airports';
        $this->flightEntitySet = 'Flights';
        $this->countryEntitySet = 'Countries';
        $this->petEntitySet = 'Pets';
        $this->entityId = 1;
        $this->missingEntityId = 99;
        $this->etag = 'W/"e4452fdbb9a32df3b92bbee272e822594ba256420eb230af48264f053ecb015c"';

        Lodata::discover(Airport::class);
        $airports = Lodata::getEntitySet('Airports');

        if (PHP_VERSION_ID < 80000) {
            $op1 = new Function_('op1');
            $op1->setCallable(function (): ?string {
                return null;
            });
            Lodata::add($op1);
            $op2 = new Function_('op2');
            $op2->setCallable(function (string $prefix): string {
                return $prefix;
            });
            Lodata::add($op2);
        }

        Lodata::discover(Flight::class);
        $flights = Lodata::getEntitySet('Flights');

        Lodata::discover(Country::class);
        $countries = Lodata::getEntitySet('Countries');

        Lodata::discover(Passenger::class);
        $passengers = Lodata::getEntitySet('Passengers');

        Lodata::discover(Pet::class);
        $pets = Lodata::getEntitySet('Pets');

        Lodata::getEntityType('Flight')->getProperty('duration')->setType(Type::duration());
        Lodata::getEntityType('Passenger')->getProperty('in_role')->setType(Type::duration());

        $airports->discoverRelationship('flights');
        $airports->discoverRelationship('country');
        $flights->discoverRelationship('passengers');
        $passengers->discoverRelationship('pets');
        $passengers->discoverRelationship('flight');
        $countries->discoverRelationship('airports');
        $passengers->discoverRelationship('originAirport');
        $passengers->discoverRelationship('destinationAirport');
        $flights->discoverRelationship('originAirport');
        $flights->discoverRelationship('destinationAirport');
        $pets->discoverRelationship('passenger');

        $airport = Lodata::getEntityType('Airport');
        $airport->getDeclaredProperty('code')->setAlternativeKey();

        $passenger = Lodata::getEntityType('Passenger');
        $passenger->getDeclaredProperty('name')->setSearchable();

        foreach ($this->getSeed() as $record) {
            (new Passenger)->newInstance($record)->save();
        }

        foreach ($this->getAirportSeed() as $record) {
            (new Airport)->newInstance($record)->save();
        }

        foreach ($this->getFlightSeed() as $record) {
            (new Flight)->newInstance($record)->save();
        }

        foreach ($this->getCountrySeed() as $record) {
            (new Country)->newInstance($record)->save();
        }

        foreach ($this->getPetSeed() as $record) {
            (new Pet)->newInstance($record)->save();
        }

        $this->captureDatabaseState();
    }

    protected function tearDownDriver()
    {
        $this->assertDatabaseDiffSnapshot();
    }

    protected function assertEloquentRecord(int $key)
    {
        /** @phpstan-ignore-next-line */
        $this->assertMatchesObjectSnapshot(Passenger::find($key)->toArray());
    }
}