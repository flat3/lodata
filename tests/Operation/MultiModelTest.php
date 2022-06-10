<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Operation;

use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\Attributes\LodataRelationship;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @requires PHP >= 8
 */
class MultiModelTest extends TestCase
{
    protected $migrations = __DIR__.'/../Laravel/migrations/muli';

    public function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
        });
    }

    public function tearDown(): void
    {
        Schema::drop('users');
        Schema::drop('orders');
        parent::tearDown();
    }

    public function test_multi()
    {
        Lodata::discover(User::class);
        $this->assertMetadataSnapshot();
    }
}

class Order extends Model
{
    #[LodataRelationship]
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    #[LodataFunction]
    public function function2(string $param)
    {
    }
}

class User extends Model
{
    #[LodataRelationship]
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    #[LodataFunction]
    public function function1(string $param)
    {
    }
}