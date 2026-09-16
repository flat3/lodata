<?php

namespace Flat3\Lodata\Tests\Octane;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use ReflectionProperty;

/**
 * Under Laravel Octane the application - and therefore the Lodata model singleton and
 * every resource it holds - stays resident in memory and is reused across many requests.
 * The request cycle must therefore never mutate a shared model resource, otherwise state
 * leaks between requests. These tests exercise the request cycle against shared resources
 * and assert that the shared instances are left untouched.
 */
class OctaneTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $type = new EntityType('a');
        $type->addProperty(new DeclaredProperty('b', Type::string()));

        // A nullable property that is not set on the singleton. Emitting the singleton
        // adds an explicit null value for it, which would accumulate on a shared instance.
        $type->addProperty(new DeclaredProperty('c', Type::string()));
        Lodata::add($type);

        $singleton = new Singleton('stest', $type);
        $pv = new PropertyValue();
        $pv->setProperty($type->getProperty('b'));
        $pv->setValue(new Type\String_('value'));
        $singleton->addPropertyValue($pv);
        Lodata::add($singleton);
    }

    /**
     * Read a protected property from an object instance.
     * @param  object  $object
     * @param  string  $property
     * @return mixed
     */
    private function getProperty($object, string $property)
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    public function test_reading_a_singleton_does_not_mutate_the_shared_instance()
    {
        /** @var Singleton $shared */
        $shared = Lodata::getSingleton('stest');

        $initialPropertyCount = $shared->getPropertyValues()->count();

        // Simulate Octane reusing the same in-memory model across multiple requests.
        for ($request = 0; $request < 3; $request++) {
            $response = $this->req(
                (new Request)
                    ->path('stest')
            );

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('"b":"value"', $response->streamedContent());
        }

        // The shared singleton retrieved from the model must be byte-for-byte the same
        // object we started with, with none of the per-request state attached to it.
        $this->assertSame($shared, Lodata::getSingleton('stest'));
        $this->assertNull($this->getProperty($shared, 'metadata'), 'Per-request metadata leaked onto the shared singleton');
        $this->assertNull($this->getProperty($shared, 'transaction'), 'Per-request transaction leaked onto the shared singleton');
        $this->assertFalse($this->getProperty($shared, 'cloned'), 'The shared singleton was marked as cloned');
        $this->assertEquals(
            $initialPropertyCount,
            $shared->getPropertyValues()->count(),
            'Property values accumulated on the shared singleton across requests'
        );
    }

    public function test_cloning_a_complex_value_isolates_property_values()
    {
        /** @var Singleton $shared */
        $shared = Lodata::getSingleton('stest');

        $clone = clone $shared;

        $pv = new PropertyValue();
        $pv->setProperty($shared->getType()->getProperty('c'));
        $pv->setValue(new Type\String_('clone-only'));
        $clone->addPropertyValue($pv);

        $this->assertCount(2, $clone->getPropertyValues());
        $this->assertCount(1, $shared->getPropertyValues());
        $this->assertNotSame(
            $this->getProperty($shared, 'propertyValues'),
            $this->getProperty($clone, 'propertyValues'),
            'Clone shares the property value collection with the original'
        );
    }
}
