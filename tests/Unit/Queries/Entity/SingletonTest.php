<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Entity;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class SingletonTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $type = new EntityType('a');
        $type->addProperty(new DeclaredProperty('b', PrimitiveType::string()));
        Lodata::add($type);

        $entity = new Singleton('atest', $type);
        $entity->setPrimitive('b', 'c');
        Lodata::add($entity);
    }

    public function test_service_document()
    {
        $this->assertJsonResponse(
            Request::factory()
        );
    }

    public function test_singleton()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest')
        );
    }
}