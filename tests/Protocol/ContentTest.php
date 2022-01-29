<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Helpers\Request;
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

        $this->assertResponseSnapshot(
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

        $this->assertResponseSnapshot(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_get_content_language()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Transaction $transaction): string {
            return $transaction->getContentLanguage();
        });
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->header(Constants::contentLanguage, 'tr')
                ->path('/op()')
        );
    }

    public function test_get_content_encoding()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Transaction $transaction): string {
            return $transaction->getContentEncoding();
        });
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->header(Constants::contentEncoding, 'utf-16')
                ->path('/op()')
        );
    }
}
