<?php

namespace Flat3\OData\Tests\Unit\Queries\Entity;

use Flat3\OData\DeclaredProperty;
use Flat3\OData\EntityType;
use Flat3\OData\Model;
use Flat3\OData\PrimitiveType;
use Flat3\OData\Singleton;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

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