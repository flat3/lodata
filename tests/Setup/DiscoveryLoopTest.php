<?php

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\Attributes\LodataIdentifier;
use Flat3\Lodata\Attributes\LodataRelationship;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @requires PHP >= 8
 */
class DiscoveryLoopTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('a_s', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('b_s', function (Blueprint $table) {
            $table->id();
        });
    }

    public function tearDown(): void
    {
        Schema::drop('a_s');
        Schema::drop('b_s');
        parent::tearDown();
    }

    public function test_loop()
    {
        Lodata::discover(A::class);
        $this->assertInstanceOf(A::class, Lodata::getEntitySet('aa')->getModel());
        $this->assertInstanceOf(B::class, Lodata::getEntitySet('bb')->getModel());
    }
}

#[LodataIdentifier('aa')]
class A extends Model
{
    #[LodataRelationship]
    public function a1()
    {
        return $this->hasMany(B::class);
    }
}

#[LodataIdentifier('bb')]
class B extends Model
{
    #[LodataRelationship]
    public function b1()
    {
        return $this->belongsTo(A::class);
    }
}