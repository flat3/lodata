<?php

namespace Flat3\Lodata\Tests\Unit\Eloquent;

use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Cast;
use Flat3\Lodata\Tests\TestCase;

class InvalidModelTest extends TestCase
{
    public function testMissingPrimaryKey()
    {
        $this->expectException(ConfigurationException::class);
        Lodata::discoverEloquentModel(Cast::class);
    }
}
