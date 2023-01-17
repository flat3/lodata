<?php

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class CustomTypeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Lodata::add(new PrimitiveType(Type\UInt16::class));
        Lodata::add(new PrimitiveType(Type\UInt32::class));
        Lodata::add(new PrimitiveType(Type\UInt64::class));
    }

    public function test_metadata()
    {
        $this->assertMetadataSnapshot();
    }

    public function test_model()
    {
        $type = new EntityType('a');
        $type->addProperty(new DeclaredProperty('b', Type::uint64()));
        $type->addProperty(new DeclaredProperty('c', Type::int64()));
        Lodata::add($type);

        $this->assertMetadataSnapshot();
    }

    public function test_add_drop_property()
    {
        $type = new EntityType('a');
        $type->addProperty(new DeclaredProperty('b', Type::uint64()));
        $this->assertEquals(1, $type->getProperties()->count());
        $type->dropProperty('b');
        $this->assertEquals(0, $type->getProperties()->count());
    }
}