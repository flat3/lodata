<?php

namespace Flat3\Lodata\Tests\Parser;

class SearchTest extends Expression
{
    public function test_0()
    {
        $this->assertSearch('t1',);
    }

    public function test_1()
    {
        $this->assertSearch('t1 OR t2',);
    }

    public function test_2()
    {
        $this->assertSearch('t1 OR t2 OR t3',);
    }

    public function test_3()
    {
        $this->assertSearch('t1 OR t2 AND t3',);
    }

    public function test_4()
    {
        $this->assertSearch('t1 OR t2 NOT t3 AND t4',);
    }

    public function test_5()
    {
        $this->assertSearch('"a t1" OR t1',);
    }

    public function test_6()
    {
        $this->assertSearch('"a \'\'t1" OR t1',);
    }

    public function test_7()
    {
        $this->assertSearch('( t1 OR t2 ) AND t3',);
    }

    public function test_8()
    {
        $this->assertSearch('(t1 OR (t2 AND t3))',);
    }

    public function test_9()
    {
        $this->assertSearch('"t1"""',);
    }

    public function test_a()
    {
        $this->assertSearch('""',);
    }
}
