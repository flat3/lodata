<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Entity;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class SingletonTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $type = new EntityType('a');
        $type->addProperty(new DeclaredProperty('b', Type::string()));
        Lodata::add($type);

        $entity = new Singleton('atest', $type);
        $pv = new PropertyValue();
        $pv->setProperty($type->getProperty('b'));
        $pv->setValue(new Type\String_('c'));
        $entity->addPropertyValue($pv);
        Lodata::add($entity);
    }

    public function test_service_document()
    {
        $this->assertJsonResponse(
            Request::factory()
        );
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_singleton()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest')
        );
    }

    public function test_singleton_property()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest/b')
        );
    }
}