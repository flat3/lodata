<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;

class DefaultExpressionTest extends TestCase
{
    protected $migrations = __DIR__.'/../Laravel/migrations/expression';

    public function test_metadata()
    {
        $set = new SQLEntitySet('dex', new EntityType('dex'));
        $set->discoverProperties();
        Lodata::add($set);

        $this->assertMetadataSnapshot();
    }
}