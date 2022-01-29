<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use function collect;

class NestingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $entityType = new EntityType('etype');
        $entityType->setKey(new DeclaredProperty('id', Type::int32()));
        $complexType = new ComplexType('ctype');
        $complexType->addDeclaredProperty('d', Type::string());

        $entityType->addProperty(new DeclaredProperty('b', Type::string()));
        $entityType->addProperty(new DeclaredProperty('c', $complexType));

        Lodata::add($complexType);
        Lodata::add($entityType);

        $set = new CollectionEntitySet('atest', $entityType);
        $set->setCollection(
            collect(
                [
                    [
                        'b' => 'c',
                        'c' => [
                            'd' => 'e',
                            'dyni' => 4,
                        ],
                    ]
                ]
            )
        );

        Lodata::add($set);
    }

    public function test_schema()
    {
        $this->assertMetadataSnapshot();
    }

    public function test_nested()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('atest')
        );
    }

    public function test_nested_path()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('atest(0)/c')
        );
    }

    public function test_double_nested_path()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('atest(0)/c/d')
        );
    }

    public function test_update_nested()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->patch()
                ->body([
                    'c' => [
                        'd' => 'q'
                    ]
                ])
                ->path('atest/0')
        );

        $this->assertMatchesSnapshot(Lodata::getEntitySet('atest')->getCollection());
    }

    public function test_update_nested_complex()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->patch()
                ->body([
                    'd' => 'q',
                ])
                ->path('atest/0/c')
        );

        $this->assertMatchesSnapshot(Lodata::getEntitySet('atest')->getCollection());
    }
}