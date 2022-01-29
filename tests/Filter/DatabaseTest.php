<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Filter;

use Flat3\Lodata\Drivers\SQLEntitySet;

abstract class DatabaseTest extends FilterTest
{
    public function test_filter_matchespattern()
    {
        $this->markTestSkippedForDriver([SQLEntitySet::SQLite, SQLEntitySet::SQLServer]);

        parent::test_filter_matchespattern();
    }

    public function test_filter_div()
    {
        $this->markTestSkippedForDriver([SQLEntitySet::SQLite, SQLEntitySet::SQLServer]);

        parent::test_filter_div();
    }

    public function test_filter_mod()
    {
        $this->markTestSkippedForDriver(SQLEntitySet::SQLServer);

        parent::test_filter_mod();
    }

    public function test_filter_ceiling()
    {
        $this->markTestSkippedForDriver(SQLEntitySet::SQLite);

        parent::test_filter_ceiling();
    }

    public function test_filter_floor()
    {
        $this->markTestSkippedForDriver(SQLEntitySet::SQLite);

        parent::test_filter_floor();
    }

    public function test_filter_substring_1()
    {
        $this->markTestSkippedForDriver(SQLEntitySet::SQLite);

        parent::test_filter_substring_1();
    }

    public function test_filter_substring_2()
    {
        $this->markTestSkippedForDriver(SQLEntitySet::SQLite);

        parent::test_filter_substring_2();
    }
}