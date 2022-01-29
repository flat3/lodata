<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\DB;

class KeylessTest extends TestCase
{
    protected $migrations = __DIR__.'/../Laravel/migrations/name';

    public function setUp(): void
    {
        parent::setUp();

        DB::table('names')->insert([
            'first_name' => 'Alice',
            'last_name' => 'Moran',
        ]);

        DB::table('names')->insert([
            'first_name' => 'Grace',
            'last_name' => 'Gumbo',
        ]);
    }

    public function withNamesModel(): void
    {
        /** @var EntityType $nameType */
        $nameType = Lodata::add(
            (new EntityType('name'))
                ->addDeclaredProperty('first_name', Type::string())
                ->addDeclaredProperty('last_name', Type::string())
        );

        Lodata::add(new SQLEntitySet('names', $nameType));
    }

    public function test_discover()
    {
        $set = new SQLEntitySet('names', new EntityType('name'));
        $set->discoverProperties();
        Lodata::add($set);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/names')
        );
    }

    public function test_query()
    {
        $this->withNamesModel();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/names')
        );
    }

    public function test_read()
    {
        $this->withNamesModel();

        $this->assertNotFound(
            (new Request)
                ->path('/names/1')
        );
    }

    public function test_delete()
    {
        $this->withNamesModel();

        $this->assertNotFound(
            (new Request)
                ->delete()
                ->path('/names/1')
        );
    }

    public function test_create()
    {
        $this->withNamesModel();

        $this->assertBadRequest(
            (new Request)
                ->post()
                ->path('/names')
                ->body([
                    'first_name' => 'felix',
                    'last_name' => 'micro',
                ])
        );
    }

    public function test_update()
    {
        $this->withNamesModel();

        $this->assertNotFound(
            (new Request)
                ->post()
                ->path('/names/1')
                ->body([
                    'first_name' => 'felix',
                    'last_name' => 'micro',
                ])
        );
    }

    public function test_paginate()
    {
        $this->withNamesModel();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->query('top', 1)
                ->path('/names')
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->query('top', 1)
                ->query('skip', 1)
                ->path('/names')
        );
    }

    public function test_metadata()
    {
        $this->withNamesModel();
        $this->assertMetadataSnapshot();
    }
}