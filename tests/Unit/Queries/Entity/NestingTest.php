<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Entity;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\ComplexValue;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class NestingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $type = new EntityType('a');
        $d = new ComplexType('d');
        $d->addDeclaredProperty('d', Type::string());

        $type->addProperty(new DeclaredProperty('b', Type::string()));
        $type->addProperty(new DeclaredProperty('c', $d));
        Lodata::add($type);

        $singleton = new Singleton('atest', $type);
        $singleton['b'] = Type\String_::factory('c');

        $c = new ComplexValue();
        $c->setType($d);
        $c['d'] = 'e';

        $singleton['c'] = $c;

        Lodata::add($singleton);
    }

    public function test_nested()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest')
        );
    }

    public function test_nested_path()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest/c')
        );
    }

    public function test_double_nested_path()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest/c/d')
        );
    }
}