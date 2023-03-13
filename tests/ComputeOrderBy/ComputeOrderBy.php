<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\ComputeOrderBy;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class ComputeOrderBy extends TestCase
{
    public function test_compute_orderby()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->compute('month(dob) as mob')
                ->orderby('mob desc')
                ->path($this->entitySetPath)
        );
    }

    public function test_compute_orderby_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->compute('month(dob) as mob')
                ->select('name,dob,mob')
                ->orderby('mob desc')
                ->path($this->entitySetPath)
        );
    }
}
