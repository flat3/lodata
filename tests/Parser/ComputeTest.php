<?php

namespace Flat3\Lodata\Tests\Parser;

class ComputeTest extends Expression
{
    public function test_00()
    {
        $this->assertCompute('origin as comp');
    }

    public function test_01()
    {
        $this->assertCompute("concat(origin, 'world') as comp");
    }

    public function test_02()
    {
        $this->assertCompute("concat(origin, 'world') as comp1, 1 add 2 as comp2");
    }

    public function test_03()
    {
        $this->assertCompute("false as comp3");
    }
}
