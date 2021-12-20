<?php

namespace Flat3\Lodata\Tests\Unit\Collection;

use Flat3\Lodata\Annotation\Core\V1\ComputedDefaultValue;
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
        $entityType->setKey((new DeclaredProperty('id', Type::int64()))->addAnnotation(new ComputedDefaultValue));
        $entityType->addDeclaredProperty('name', Type::string());
        $entityType->getDeclaredProperty('name')->setSearchable();
        $entitySet = new CollectionEntitySet('examples', $entityType);
        $entitySet->setCollection($collection);

        Lodata::add($entitySet);
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_count()
    {
        $this->assertTextResponse(
            (new Request)
                ->text()
                ->path('/examples/$count')
        );
    }

    public function test_all()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/examples')
        );
    }

    public function test_all_metadata()
    {
        $this->assertJsonResponse(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path('/examples')
        );
    }

    public function test_create()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->post()
                ->path('/examples')
                ->body([
                    'name' => 'Zeta',
                ])
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/examples')
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/examples/3')
        );
    }

    public function test_create_positional()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->post()
                ->path('/examples')
                ->index(1)
                ->body([
                    'name' => 'Zeta',
                ])
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/examples')
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/examples/1')
        );
    }

    public function test_read()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/examples/2')
        );
    }

    public function test_missing()
    {
        $this->assertNotFound(
            (new Request)
                ->path('/examples/99')
        );
    }

    public function test_delete()
    {
        $this->assertNoContent(
            (new Request)
                ->delete()
                ->path('/examples/1')
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/examples')
        );
    }

    public function test_update()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/examples/0')
                ->patch()
                ->body([
                    'name' => 'Alph',
                ])
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/examples')
        );
    }
}