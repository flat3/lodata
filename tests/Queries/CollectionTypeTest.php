<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\CollectionType;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Collection;

class CollectionTypeTest extends TestCase
{
    public function test_value_invalid_name()
    {
        $type = new CollectionType();
        $collection = $type->instance();
        $collection[] = 'a';
        $collection[] = 'b';
        $this->assertEquals(['a', 'b'], $collection->toMixed());
    }

    public function test_singleton()
    {
        $type = new EntityType('test');
        $type->addDeclaredProperty('col', Type::collection(Type::string()));
        $entity = new Singleton('example', $type);
        $entity['col'] = ['aaa'];
        Lodata::add($entity);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/example')
        );
    }

    public function test_singleton_split()
    {
        $type = new EntityType('test');

        $type->addDeclaredProperty('col', Type::collection(Type::string()));

        $entity = new Singleton('example', $type);
        $collection = new Collection();
        $collection[] = 'aaa';
        $entity['col'] = $collection;

        Lodata::add($entity);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/example')
        );
    }

    public function test_singleton_append()
    {
        $type = new EntityType('test');
        $type->addDeclaredProperty('col', Type::collection(Type::string()));
        $entity = new Singleton('example', $type);
        $entity['col'] = ['aaa'];
        $entity['col']->getValue()[] = 'bbb';
        Lodata::add($entity);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/example')
        );
    }

    public function test_singleton_invalid_collection()
    {
        $this->expectExceptionMessage('The value provided for the collection was not formed as an array');
        $type = new EntityType('test');
        $type->addDeclaredProperty('col', Type::collection(Type::int64()));
        $entity = new Singleton('example', $type);
        $entity['col'] = 'aaa';
    }

    public function test_complex()
    {
        $c = new ComplexType('cplx');
        $c->addDeclaredProperty('one', Type::string());
        $c->addDeclaredProperty('two', Type::int64());

        $type = new EntityType('test');
        $type->addDeclaredProperty('col', Type::collection($c));
        $entity = new Singleton('example', $type);
        $entity['col'] = [
            [
                'one' => 'one',
                'two' => 2,
            ],
            [
                'one' => 'two',
                'two' => 3,
            ],
        ];
        Lodata::add($entity);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/example')
        );
    }

    public function test_dynamic_complex()
    {
        $type = new EntityType('test');
        $entity = new Singleton('example', $type);
        $entity['col'] = [
            [
                'one' => 'one',
                'two' => 2,
            ],
            [
                'one' => 'two',
                'two' => 3,
            ],
        ];
        Lodata::add($entity);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/example')
        );
    }

    public function test_dynamic_collection()
    {
        $type = new EntityType('test');
        $entity = new Singleton('example', $type);
        $entity['col'] = [
            [
                'one',
                'two',
            ],
            [
                'three',
                'four',
            ],
        ];
        Lodata::add($entity);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/example')
        );
    }
}