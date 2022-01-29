<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use function collect;

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
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('cset')
        );
    }

    public function test_dynamic_property_path()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("cset('first')/dynamicint")
        );
    }

    public function test_update_dynamic_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->patch()
                ->path("cset('first')/dynamicint")
                ->body(5)
        );
    }
}