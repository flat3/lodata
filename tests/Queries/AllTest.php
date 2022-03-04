<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

class AllTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_read_all()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$all')
        );
    }

    public function test_read_all_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$all')
                ->select('id')
        );
    }

    public function test_read_all_orderby()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$all')
                ->orderby('id desc')
        );
    }

    public function test_read_all_metadata()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$all')
                ->metadata(MetadataType\Full::name)
        );
    }

    public function test_read_all_singleton()
    {
        $this->withSingleton();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$all')
        );
    }

    public function test_read_all_singleton_metadata()
    {
        $this->withSingleton();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$all')
                ->metadata(MetadataType\Full::name)
        );
    }

    public function test_read_all_type()
    {
        Lodata::add(new EntityType('test'));

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$all/test')
        );
    }

    public function test_bad_type()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/$all/flight')
        );
    }

    public function test_read_empty()
    {
        Lodata::add(new CollectionEntitySet(new EntityType('basic')));

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$all')
        );
    }
}