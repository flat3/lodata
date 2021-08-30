<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Entity;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class DynamicPropertyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $set = new CollectionEntitySet('cset');
        $type = new EntityType('etype');
        $type->setKey(new DeclaredProperty('id', Type::string()));
        $type->addDeclaredProperty('defined', Type::string());
        $set->setType($type);

        $data = [
            'first' => [
                'defined' => 'c',
                'dynamicstring' => 'b',
                'dynamicint' => 4,
                'dynamicfloat' => 2.2,
            ]
        ];

        $set->setCollection(collect($data));

        Lodata::add($set);
    }

    public function test_dynamic_property()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('cset')
        );
    }

    public function test_dynamic_property_path()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("cset('first')/dynamicint")
        );
    }

    public function test_update_dynamic_property()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->patch()
                ->path("cset('first')/dynamicint")
                ->body(5)
        );
    }
}