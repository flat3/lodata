<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Term;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\EnumMember;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\String_;
use TypeError;

class EnumerationTypeTest extends TestCase
{
    public function test_metadata()
    {
        $type = new EnumerationType('hello');
        $type[] = 'aaa';
        $type[] = 'bbb';
        Lodata::add($type);

        $this->assertMetadataSnapshot();
    }

    public function test_metadata_flags()
    {
        $type = new EnumerationType('hello');
        $type->setIsFlags();
        $type[] = 'aaa';
        $type[] = 'bbb';
        Lodata::add($type);

        $this->assertMetadataSnapshot();
    }

    public function test_metadata_terms()
    {
        $type = new EnumerationType('hello');
        $type->getMembers()->add(
            (new EnumMember($type))
                ->setValue(1)
                ->setName('first')
                ->addAnnotation(
                    (new Annotation)
                        ->setIdentifier('Example.Name')
                        ->setValue(new String_('Double Wide'))
                )
        );
        Lodata::add($type);
        $this->assertMetadataSnapshot();
    }

    public function test_enum_must_be_integer()
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(PHP_VERSION_ID >= 80000 ? 'must be of type int, string given' : 'must be of the type int, string given');
        $type = new EnumerationType('hello');
        $type['aaa'] = '1';
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

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/example')
        );
    }

    public function test_singleton_no_flags()
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

        $this->assertJsonResponseSnapshot(
            (new Request)
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

        $this->assertJsonResponseSnapshot(
            (new Request)
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

        $this->assertJsonResponseSnapshot(
            (new Request)
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

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->metadata(MetadataType::Full)
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

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('The provided flag value "bbb" was not valid for this type');
        $entity['inner'] = 'bbb';
    }

    public function test_singleton_collection_enum()
    {
        $enu = new EnumerationType('enu');
        $enu[] = 'aaa';
        $enu[] = 'bbb';
        Lodata::add($enu);

        $ston = new EntityType('ston');
        $ston->addDeclaredProperty('ecol', Type::collection($enu));

        $entity = new Singleton('exa', $ston);
        $entity['ecol'] = ['aaa', 'bbb'];

        Lodata::add($entity);

        $this->assertMetadataSnapshot();
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->metadata(MetadataType::Full)
                ->path('/exa')
        );
    }
}