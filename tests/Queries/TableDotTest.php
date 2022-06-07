<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\DB;

class TableDotTest extends TestCase
{
    protected $migrations = __DIR__.'/../Laravel/migrations/dot';

    public function setUp(): void
    {
        parent::setUp();

        $this->markTestSkippedForDriver([SQLEntitySet::SQLite]);

        DB::statement("INSERT INTO dots values ('Alice','Moran')");
        DB::statement("INSERT INTO dots values ('Grace','Gumbo')");
    }

    public function test_discovery()
    {
        $set = new SQLEntitySet('dots', new EntityType('dot'));
        $set->discoverProperties();
        Lodata::add($set);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/dots')
        );
    }

    public function test_without_discovery()
    {
        /** @var EntityType $type */
        $type = Lodata::add(
            (new EntityType('dot'))
                ->addDeclaredProperty('first_name', Type::string())
                ->addDeclaredProperty('last_name', Type::string())
        );

        $set = new SQLEntitySet('dots', $type);
        $set->setPropertySourceName($type->getProperty('first_name'), 'name.first');
        $set->setPropertySourceName($type->getProperty('last_name'), 'name.last');

        Lodata::add($set);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/dots')
        );
    }
}