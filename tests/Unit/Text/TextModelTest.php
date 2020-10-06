<?php

namespace Flat3\OData\Tests\Unit\Text;

use Flat3\OData\Tests\Data\TextModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class TextModelTest extends TestCase
{
    use TextModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withTextModel();
    }

    public function test_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/texts')
        );
    }

    public function test_rejects_filter()
    {
        $this->assertNotImplemented(
            Request::factory()
                ->path('/texts')
                ->filter("a eq 'b'")
        );
    }
}