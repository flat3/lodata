<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Setup;

use Doctrine\DBAL\Schema\Table;
use Flat3\Lodata\Drivers\MongoEntitySet;
use Flat3\Lodata\Drivers\MongoEntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Laravel\Models\Airport;
use Flat3\Lodata\Tests\Laravel\Models\Pet;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class DiscoveryTest extends TestCase
{
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        config(['database.default' => 'testing']);
    }

    public function test_default_ttl()
    {
        Lodata::discoverEloquentModel(Airport::class);
        $table = Cache::store('redis')->get('lodata.discovery.sql.testing.airports');
        $this->assertNull($table);
    }

    public function test_zero_ttl()
    {
        config([
            'lodata.discovery.store' => 'redis',
            'lodata.discovery.ttl' => 0,
        ]);

        Lodata::discoverEloquentModel(Airport::class);
        $table = Cache::store('redis')->get('lodata.discovery.sql.testing.airports');
        $this->assertNull($table);
    }

    public function test_empty_ttl()
    {
        config([
            'lodata.discovery.store' => 'redis',
            'lodata.discovery.ttl' => '',
        ]);

        Lodata::discoverEloquentModel(Airport::class);
        $table = Cache::store('redis')->get('lodata.discovery.sql.testing.airports');
        $this->assertNull($table);
    }

    public function test_cache_null()
    {
        config([
            'lodata.discovery.store' => 'redis',
            'lodata.discovery.ttl' => null,
        ]);

        Lodata::discoverEloquentModel(Airport::class);
        $table = Cache::store('redis')->get('lodata.discovery.sql.testing.airports');
        $this->assertInstanceOf(Table::class, $table);
        $this->assertEquals(-2, Cache::store('redis')->getRedis()->ttl('lodata.discovery.sql.testing.airports'));
    }

    public function test_default_config()
    {
        $this->assertNull(config('lodata.discovery.store'));
        $this->assertEquals(0, config('lodata.discovery.ttl'));
    }

    public function test_no_duplicate_discovery()
    {
        Lodata::discover(Pet::class);
        $set = Lodata::getEntitySet('Pets');
        $type = Lodata::getEntityType('Pet');
        Lodata::discover(Pet::class);
        $this->assertSame($set, Lodata::getEntitySet('Pets'));
        $this->assertSame($type, Lodata::getEntityType('Pet'));
    }

    /**
     * @group mongo
     */
    public function test_mongo_collection()
    {
        Lodata::discover((new \MongoDB\Client)->test->passengers);

        $set = Lodata::getEntitySet('passengers');
        $type = Lodata::getEntityType('passenger');

        $this->assertInstanceOf(MongoEntitySet::class, $set);
        $this->assertInstanceOf(MongoEntityType::class, $type);
    }
}