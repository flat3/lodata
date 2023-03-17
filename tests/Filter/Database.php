<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Filter;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\Tests\Helpers\Request;

abstract class Database extends Filter
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
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $this->markTestSkippedForDriver(SQLEntitySet::SQLite);
        }

        parent::test_filter_substring_1();
    }

    public function test_filter_substring_2()
    {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $this->markTestSkippedForDriver(SQLEntitySet::SQLite);
        }

        parent::test_filter_substring_2();
    }

    public function test_filter_cast()
    {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $this->markTestSkippedForDriver(SQLEntitySet::SQLite);
        }

        parent::test_filter_cast();
    }

    public function test_filter_any()
    {
        $this->markTestSkippedForDriver(SQLEntitySet::SQLite);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySet)
                ->filter("MyPets/any(f:f/type eq 'dog')")
                ->expand('MyPets')
        );
    }

    public function test_filter_all()
    {
        $this->markTestSkippedForDriver(SQLEntitySet::SQLite);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySet)
                ->filter("MyPets/all(f:f/type eq 'cat')")
                ->expand('MyPets')
        );
    }
}
