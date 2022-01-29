<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;

class DefaultValueTest extends TestCase
{
    protected $migrations = __DIR__.'/../Laravel/migrations/defaults';

    public function test_default()
    {
        $set = (new SQLEntitySet('tests', new EntityType('test')))->setTable('test');
        $set->discoverProperties();
        Lodata::add($set);

        $this->assertMetadataSnapshot();
    }
}