<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Count;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class Count extends TestCase
{
    public function test_count_path()
    {
        $this->assertTextResponseSnapshot(
            (new Request)
                ->text()
                ->path($this->entitySetPath.'/$count')
        );
    }

    public function test_count_path_ignores_top()
    {
        $this->assertTextResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$count')
                ->text()
                ->top('1')
        );
    }

    public function test_count_path_ignores_skip()
    {
        $this->assertTextResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$count')
                ->text()
                ->skip('1')
        );
    }

    public function test_count()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->count('true')
        );
    }

    public function test_count_ignores_top()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->top('1')
                ->count('true')
        );
    }

    public function test_count_ignores_skip()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->skip('1')
                ->count('true')
        );
    }

    public function test_count_false()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->count('false')
        );
    }

    public function test_count_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->query('$count', 'invalid')
        );
    }

    public function test_count_zero_top()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->query('$count', 'true')
                ->top('0')
        );
    }
}
