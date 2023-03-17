<?php

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Laravel\Models\Name;
use Flat3\Lodata\Tests\TestCase;

class InvalidModelTest extends TestCase
{
    protected $migrations = __DIR__.'/../Laravel/migrations/cast';

    public function testMissingPrimaryKey()
    {
        if ($this->getConnection()->getDriverName() !== SQLEntitySet::SQLite) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The model Flat3\Lodata\Tests\Laravel\Models\Name had no primary key');
        Lodata::discoverEloquentModel(Name::class);
    }
}
