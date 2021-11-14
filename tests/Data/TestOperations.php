<?php

namespace Flat3\Lodata\Tests\Data;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;

trait TestOperations
{
    public function withNumberFunction()
    {
        $number = new Operation\Function_('number');
        $number->setCallable(function (): int {
            return 42;
        });

        Lodata::add($number);
    }
}