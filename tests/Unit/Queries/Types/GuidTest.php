<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Types;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuidTest extends TypeTest
{
    public function test_filter_guid()
    {
        DB::statement('CREATE TABLE examples (id BLOB NOT NULL)');

        Lodata::add(
            (new SQLEntitySet(
                'examples',
                (new EntityType('example'))
                    ->setKey(new DeclaredProperty('id', Type::guid()))
            ))
                ->setTable('examples')
        );

        $this->uuid = 81237765883;

        $this->assertJsonMetadataResponse(
            (new Request)
                ->path('/examples')
                ->body([
                    'id' => Str::uuid(),
                ])
                ->post()
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/examples')
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/examples(00000000-0000-0000-0000-0012EA25EEFB)')
        );
    }
}