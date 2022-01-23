<?php

namespace Flat3\Lodata\Tests\Unit\Eloquent;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Cast;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int64;

class CastingTest extends TestCase
{
    protected $migrations = __DIR__.'/../../migrations/cast';

    public function setUp(): void
    {
        parent::setUp();

        (new Cast([
            'id' => '5',
        ]))->save();

        Lodata::discover(Cast::class);
    }

    public function testPrimaryKeyCast()
    {
        $type = Lodata::getEntityType('Cast');
        $this->assertTrue($type->getKey()->getType()->instance() instanceof Int64);
    }

    public function testPrimaryKeyCastRequest()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/Casts(5)')
        );

        $this->assertBadRequest(
            (new Request)
                ->path("/Casts('1')")
        );
    }
}