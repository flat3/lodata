<?php

namespace Flat3\OData\Tests\Unit\Text;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class TextModelTest extends TestCase
{
    public function test_set()
    {
        $this->withTextModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/texts')
        );
    }

    public function test_rejects_filter()
    {
        $this->withTextModel();

        $this->assertNotImplemented(
            Request::factory()
                ->path('/texts')
                ->filter("a eq 'b'")
        );
    }
}