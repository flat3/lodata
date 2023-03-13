<?php

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Example extends Model
{
    protected $connection = 'example';
}

class ConnectionTest extends TestCase
{
    public function test_connection_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database connection [example] not configured.');
        Lodata::discoverEloquentModel(Example::class);
    }
}
