<?php

namespace Flat3\Lodata\Tests\Unit\Collection;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type;

class NumericCollectionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $collection = collect([
            [
                'name' => 'Alpha',
            ],
            [
                'name' => 'Beta',
            ],
            [
                'name' => 'Gamma',
            ],
            [
                'name' => 'Delta',
            ],
        ]);
        $entityType = new EntityType('example');
        $entityType->setKey(new DeclaredProperty('id', Type::int64()));
        $entityType->addDeclaredProperty('name', Type::string());
        $entityType->getDeclaredProperty('name')->setSearchable();
        $entitySet = new CollectionEntitySet('examples', $entityType);
        $entitySet->setCollection($collection);

        Lodata::add($entitySet);
    }

    public function test_count()
    {
        $this->assertTextResponse(
            Request::factory()
                ->text()
                ->path('/examples/$count')
        );
    }

    public function test_all()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/examples')
        );
    }

    public function test_all_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(MetadataType\Full::name)
                ->path('/examples')
        );
    }

    public function test_create()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->post()
                ->path('/examples')
                ->body([
                    'name' => 'Zeta',
                ])
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/examples')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/examples/3')
        );
    }

    public function test_read()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/examples/2')
        );
    }

    public function test_missing()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/examples/99')
        );
    }

    public function test_delete()
    {
        $this->assertNoContent(
            Request::factory()
                ->delete()
                ->path('/examples/1')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/examples')
        );
    }

    public function test_update()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/examples/0')
                ->patch()
                ->body([
                    'name' => 'Alph',
                ])
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/examples')
        );
    }
}