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

        Lodata::discover(Airport::class);
        $airports = Lodata::getEntitySet('Airports');

        if (version_compare(PHP_VERSION, '8.0', '<')) {
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
        $passengerType = $passengers->getType();

        Lodata::discover(Pet::class);
        $pets = Lodata::getEntitySet('Pets');

        if (!Lodata::getTypeDefinition('colour')) {
            $this->addEnumerationTypes();
            $passengerType->addDeclaredProperty('colour', Lodata::getTypeDefinition('Colours'));
            $passengerType->addDeclaredProperty('sock_colours', Lodata::getTypeDefinition('MultiColours'));
        }

        Lodata::getEntityType('Flight')->getProperty('duration')->setType(Type::duration());
        Lodata::getEntityType('Passenger')->getProperty('in_role')->setType(Type::duration());

        $airports->discoverRelationship('flights');
        $airports->discoverRelationship('country');
        $flights->discoverRelationship('passengers');
        $passengers->discoverRelationship('pets', 'MyPets', 'All my pets');
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
        $passenger->getDeclaredProperty('name')->setSearchable()->setMaxLength(255);

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