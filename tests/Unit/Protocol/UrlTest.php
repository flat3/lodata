<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class UrlTest extends TestCase
{
    public $tests = [
        "/t2('O''Neil')" => "O'Neil",
        '/t2(%27O%27%27Neil%27)' => "O'Neil",
        '/t2%28%27O%27%27Neil%27%29' => "O'Neil",
        '/t2(\'Smartphone%2FTablet\')' => 'Smartphone/Tablet',
    ];

    public function test_valid_urls()
    {
        $this->markTestIncomplete();
    }

    public function test_invalid_urls_1()
    {
        $this->markTestIncomplete();
        $this->assertBadRequest(
            Request::factory()
                ->path("/t2('O'Neil')")
        );
    }

    public function test_invalid_urls_2()
    {
        $this->markTestIncomplete();
        $this->assertBadRequest(
            Request::factory()
                ->path("/t2('O%27Neil')")
        );
    }

    public function test_invalid_urls_3()
    {
        $this->markTestIncomplete();
        $this->assertBadRequest(
            Request::factory()
                ->path("/t2(\'Smartphone/Tablet\')")
        );
    }
}
