<?php

namespace Flat3\Lodata\Tests\Parser;

class LambdaTest extends Expression
{
    public function test_5b()
    {
        $this->assertLambda("airports/any(d:d/name eq 'hello')");
    }

    public function test_5d()
    {
        $this->assertLambda("airports/all(d:d/name eq 'hello')");
    }

    public function test_5e()
    {
        $this->assertLambda("da/all(d:d/name eq 'hello')");
    }

    public function test_5f()
    {
        $this->assertLambda("airports/any(d:d/name eq 'hello') and airports/any(d:d/name eq 'hello')");
    }

    public function test_5g()
    {
        $this->assertLambda("airports/any(d:d/name eq 'hello') and 1 eq 2 or airports/all(d:d/name eq 'hello')");
    }

    public function test_count_single_constraint()
    {
        $this->assertLambda('da/$count eq 1');
    }

    public function test_count_multiple_constraints()
    {
        $this->assertLambda('airports/$count gt 0');
    }

    public function test_count_logical_combination()
    {
        $this->assertLambda('da/$count ge 2 or airports/$count eq 0');
    }
}
