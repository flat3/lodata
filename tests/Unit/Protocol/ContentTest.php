<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\String_;

class ContentTest extends TestCase
{
    public function test_content_encoding()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (Transaction $transaction): String_ {
            $transaction->setContentEncoding('identity');
            return new String_('hello');
        });
        Lodata::add($exf1);

        $this->assertMetadataResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_content_language()
    {
        $exf1 = new Operation\Function_('exf1');
        $exf1->setCallable(function (Transaction $transaction): String_ {
            $transaction->setContentLanguage('fr');
            return new String_('hello');
        });
        Lodata::add($exf1);

        $this->assertMetadataResponse(
            (new Request)
                ->path('/exf1()')
        );
    }
}
