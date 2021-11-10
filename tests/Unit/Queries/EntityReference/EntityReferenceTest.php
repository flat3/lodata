<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntityReference;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

class EntityReferenceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_not_entity_or_set_not_found()
    {
        $this->assertNotFound(
            (new Request)
                ->path('/flights(1)/origin/$ref')
        );
    }

    public function test_not_last_segment()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/flights(1)/$ref/1')
        );
    }

    public function test_entity_set_references()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights/$ref')
        );
    }

    public function test_entity_references()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)/$ref')
        );
    }

    public function test_entity_set_references_full_metadata()
    {
        $this->assertJsonResponse(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path('/flights/$ref')
        );
    }
}

