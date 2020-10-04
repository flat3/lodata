<?php

namespace Flat3\OData\Tests\Unit\Text;

use Flat3\OData\DeclaredProperty;
use Flat3\OData\EntitySet;
use Flat3\OData\Model;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type;

class TextModelTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Model::add(
            new class(
                'texts',
                Model::entitytype('text')
                    ->addProperty(DeclaredProperty::factory('a', Type::string()))
            ) extends EntitySet {
                public function generate(): array
                {
                    return [
                        $this->makeEntity()
                            ->setPrimitive('a', 'a')
                    ];
                }
            });
    }

    public function test_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/texts')
        );
    }

    public function test_rejects_filter()
    {
        $this->assertNotImplemented(
            Request::factory()
                ->path('/texts')
                ->filter("a eq 'b'")
        );
    }
}