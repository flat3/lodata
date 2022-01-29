<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

class ContextResourceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withTextModel();
        $this->withSingleton();
    }

    public function test_singleton()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path('/sInstance')
        );
    }

    public function test_operation_result()
    {
        $textf1 = new Operation\Function_('textf1');
        $textf1->setCallable(function (EntitySet $texts): EntitySet {
            return $texts;
        });
        $textf1->setReturnType(Lodata::getEntityType('text'));
        Lodata::add($textf1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path('/textf1()')
        );
    }
}