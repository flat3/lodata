<?php

namespace Flat3\OData\Tests\Unit\Text;

use Flat3\OData\EntitySet;
use Flat3\OData\ODataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type;

class TextModelTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        ODataModel::add(
            new class(
                'texts',
                ODataModel::entitytype('text')
                    ->addDeclaredProperty('a', Type::string())
            ) extends EntitySet {
                public function generate(): array
                {
                    return [
                        $this->entity()
                            ->addPrimitive('a', 'a')
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