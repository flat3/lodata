<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithSQLDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * @group sql
 */
class UrlTest extends TestCase
{
    use WithSQLDriver;

    public function setUp(): void
    {
        parent::setUp();

        $airportType = Lodata::getEntityType('passenger');
        $airportType->getDeclaredProperty('name')->setSearchable()->setAlternativeKey();
        $airportType->getDeclaredProperty('dob')->setSearchable()->setAlternativeKey();

        DB::table('passengers')->insert([
            'name' => "O'Hare",
        ]);

        DB::table('passengers')->insert([
            'name' => "Zig/Zag",
        ]);
    }

    public function test_valid_1()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."(name='O''Hare')")
        );
    }

    public function test_valid_2()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'(name%3D%27O%27%27Hare%27)')
        );
    }

    public function test_valid_3()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'%28name%3D%27O%27%27Hare%27%29')
        );
    }

    public function test_valid_4()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."(name='Zig%2FZag')")
        );
    }

    public function test_invalid_urls_1()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath."('O'Hare')")
        );
    }

    public function test_invalid_urls_2()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath."(name='O%27Hare')")
        );
    }

    public function test_invalid_urls_3()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath."('Zig/Zag')")
        );
    }
}
