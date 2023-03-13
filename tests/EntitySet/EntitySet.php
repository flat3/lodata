<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntitySet;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

abstract class EntitySet extends TestCase
{
    protected $selectProperty = 'name';

    public function test_metadata()
    {
        $this->assertMetadataSnapshot();
    }

    public function test_all()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );
    }

    public function test_all_metadata()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path($this->entitySetPath)
        );
    }

    public function test_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->select($this->selectProperty)
        );
    }

    public function test_entity_set_references()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$ref')
        );
    }

    public function test_entity_set_references_full_metadata()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path($this->entitySetPath.'/$ref')
        );
    }

    public function test_select_existent_property_full_metadata_ignoring_nulls()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->metadata(MetadataType\Full::name)
                ->preference('omit-values', 'nulls')
                ->select($this->selectProperty.',age')
        );
    }
}
