<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class ParameterAliasTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_alias()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('name eq @code')
                ->query('@code', "'Alpha'")
        );
    }

    public function test_alias_date()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dq eq @code')
                ->query('@code', '2002-03-03')
        );
    }

    public function test_alias_bool()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('chips eq @code')
                ->query('@code', 'true')
        );
    }

    public function test_nonexistent_alias()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('code eq @code')
        );
    }
}
