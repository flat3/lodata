<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\OrderBy;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class OrderBy extends TestCase
{
    public function test_orderby()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->orderby('name')
                ->path($this->entitySetPath)
        );
    }

    public function test_orderby_multiple()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->orderby('name asc, age desc')
                ->path($this->entitySetPath)
        );
    }

    public function test_orderby_desc()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('name desc')
        );
    }

    public function test_orderby_asc()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('name asc')
        );
    }

    public function test_orderby_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('name wrong')
        );
    }

    public function test_orderby_invalid_property()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('invalid asc')
        );
    }

    public function test_orderby_invalid_multiple()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('name asc age desc')
        );
    }
}
