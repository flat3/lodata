<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Compute;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class Compute extends TestCase
{
    protected $computeString = 'name';
    protected $computeDate = 'dob';
    protected $computeFloat = 'age';

    public function test_compute()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->compute(sprintf("concat(%s, ' is my name') as myName", $this->computeString))
                ->path($this->entityPath)
        );
    }

    public function test_compute_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->compute(sprintf("concat(%s, ' is my name') as myName", $this->computeString))
                ->path($this->entityPath.'/myName')
        );
    }

    public function test_compute_set()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->compute(sprintf("concat(%s, ' is my name') as myName", $this->computeString))
                ->path($this->entitySetPath)
        );
    }

    public function test_compute_math()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->compute(sprintf("%s add 4.4 as age44", $this->computeFloat))
                ->path($this->entityPath)
        );
    }

    public function test_compute_complex()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->compute(sprintf("year(%s) add month(%s) as testprop", $this->computeDate, $this->computeDate))
        );
    }
}
