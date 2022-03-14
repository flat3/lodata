<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Laravel\Models\Passenger;
use Flat3\Lodata\Tests\TestCase;

class PassengerHidden extends Passenger
{
    protected $hidden = ['age'];
    protected $table = 'passengers';
}

class PassengerVisible extends Passenger
{
    protected $visible = ['id', 'age'];
    protected $table = 'passengers';
}

class HiddenTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Lodata::discover(PassengerHidden::class);
        Lodata::discover(PassengerVisible::class);
    }

    public function test_hidden()
    {
        $this->assertNull(Lodata::getEntityType('PassengerHidden')->getDeclaredProperty('age'));
        $this->assertNotNull(Lodata::getEntityType('PassengerHidden')->getDeclaredProperty('dob'));
    }

    public function test_visible()
    {
        $this->assertNotNull(Lodata::getEntityType('PassengerVisible')->getDeclaredProperty('age'));
        $this->assertNull(Lodata::getEntityType('PassengerVisible')->getDeclaredProperty('dob'));
    }
}