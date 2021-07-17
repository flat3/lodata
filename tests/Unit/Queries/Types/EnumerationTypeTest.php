<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Types;

use Flat3\Lodata\EntityType;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

class EnumerationTypeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_metadata()
    {
        $type = new EnumerationType('hello');
        $type[] = 'aaa';
        $type[] = 'bbb';
        Lodata::add($type);

        $this->assertMetadataDocuments();
    }

    public function test_metadata_flags()
    {
        $type = new EnumerationType('hello');
        $type->setIsFlags();
        $type[] = 'aaa';
        $type[] = 'bbb';
        Lodata::add($type);

        $this->assertMetadataDocuments();
    }

    public function test_value_invalid_name()
    {
        try {
            $type = new EnumerationType('hello');
            $type['aaa'] = '1';
        } catch (InternalServerErrorException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }

    public function test_singleton()
    {
        $type = new EntityType('outer');
        $enumeration = new EnumerationType('inner');
        $enumeration[] = 'aaa';
        Lodata::add($enumeration);
        $type->addDeclaredProperty('inner', $enumeration);
        $entity = new Singleton('example', $type);
        $entity['inner'] = 'aaa';
        Lodata::add($entity);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example')
        );
    }

    public function test_singleton_no_flags()
    {
        $type = new EntityType('outer');
        $enumeration = new EnumerationType('inner');
        $enumeration[] = 'aaa';
        $enumeration[] = 'bbb';
        Lodata::add($enumeration);
        $type->addDeclaredProperty('inner', $enumeration);
        $entity = new Singleton('example', $type);
        $entity['inner'] = 'aaa,bbb';
        Lodata::add($entity);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example')
        );
    }

    public function test_singleton_flags()
    {
        $type = new EntityType('outer');
        $enumeration = new EnumerationType('inner');
        $enumeration->setIsFlags();
        $enumeration[] = 'aaa';
        $enumeration[] = 'bbb';
        Lodata::add($enumeration);
        $type->addDeclaredProperty('inner', $enumeration);
        $entity = new Singleton('example', $type);
        $entity['inner'] = 'aaa,bbb';
        Lodata::add($entity);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example')
        );
    }

    public function test_singleton_property()
    {
        $type = new EntityType('outer');
        $enumeration = new EnumerationType('inner');
        $enumeration->setIsFlags();
        $enumeration[] = 'aaa';
        $enumeration[] = 'bbb';
        Lodata::add($enumeration);
        $type->addDeclaredProperty('inner', $enumeration);
        $entity = new Singleton('example', $type);
        $entity['inner'] = 'aaa,bbb';
        Lodata::add($entity);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example/inner')
        );
    }

    public function test_singleton_flags_full_metadata()
    {
        $type = new EntityType('outer');
        $enumeration = new EnumerationType('inner');
        $enumeration->setIsFlags();
        $enumeration[] = 'aaa';
        $enumeration[] = 'bbb';
        Lodata::add($enumeration);
        $type->addDeclaredProperty('inner', $enumeration);
        $entity = new Singleton('example', $type);
        $entity['inner'] = 'aaa,bbb';
        Lodata::add($entity);

        $this->assertJsonResponse(
            Request::factory()
                ->metadata(MetadataType\Full::name)
                ->path('/example')
        );
    }

    public function test_singleton_bad_enumeration_value()
    {
        $type = new EntityType('outer');
        $enumeration = new EnumerationType('inner');
        $enumeration[] = 'aaa';
        Lodata::add($enumeration);
        $type->addDeclaredProperty('inner', $enumeration);
        $entity = new Singleton('example', $type);

        try {
            $entity['inner'] = 'bbb';
        } catch (InternalServerErrorException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }
}