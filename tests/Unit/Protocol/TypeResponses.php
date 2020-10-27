<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Binary;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Guid;
use Flat3\Lodata\Type\Int16;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\Int64;

class TypeResponses extends TestCase
{
    public function test_inf()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Double
            {
                return new Double(INF);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_negative_inf()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Double
            {
                return new Double(-INF);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_nan()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Double
            {
                return new Double(NAN);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_true()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Boolean
            {
                return new Boolean(true);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_false()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Boolean
            {
                return new Boolean(false);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_int64()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Int64
            {
                return new Int64(PHP_INT_MAX);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_int32()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Int32
            {
                return new Int32((2 ** 31) - 1);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_int16()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Int16
            {
                return new Int16((2 ** 15) - 1);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_int64_overflow()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Int64
            {
                return new Int64(PHP_INT_MAX + 1);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_int32_overflow()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Int32
            {
                return new Int32(2 ** 31);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_int16_overflow()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Int16
            {
                return new Int16(2 ** 15);
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_binary()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Binary
            {
                return new Binary('R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_guid()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): Guid
            {
                return new Guid('00000000-1111-2222-3333-444455556666');
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }
}
