<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Facades\Lodata;
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

class TypeResponsesTest extends TestCase
{
    public function test_inf()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Double {
            return new Double(INF);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_negative_inf()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Double {
            return new Double(-INF);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_nan()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Double {
            return new Double(NAN);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_true()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Boolean {
            return new Boolean(true);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_false()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Boolean {
            return new Boolean(false);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_int64()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Int64 {
            return new Int64(PHP_INT_MAX);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_int32()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Int32 {
            return new Int32((2 ** 31) - 1);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_int16()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Int16 {
            return new Int16((2 ** 15) - 1);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_int64_overflow()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Int64 {
            return new Int64(PHP_INT_MAX + 1);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_int32_overflow()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Int32 {
            return new Int32(2 ** 31);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_int16_overflow()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Int16 {
            return new Int16(2 ** 15);
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_binary()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Binary {
            return new Binary('R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_guid()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (): Guid {
            return new Guid('00000000-1111-2222-3333-444455556666');
        });
        Lodata::add($exf1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }
}
