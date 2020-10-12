<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Entity;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Model;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class SingletonTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $model = Model::get();

        $type = new EntityType('a');
        $type->addProperty(DeclaredProperty::factory('b', PrimitiveType::string()));
        $model::add($type);

        $entity = new Singleton('atest', $type);
        $entity->setPrimitive('b', 'c');
        $model::add($entity);
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