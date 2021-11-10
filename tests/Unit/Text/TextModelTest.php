<?php

namespace Flat3\Lodata\Tests\Unit\Text;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class TextModelTest extends TestCase
{
    public function test_set()
    {
        $this->withTextModel();

        $this->assertJsonResponse(
            (new Request)
                ->path('/texts')
        );
    }

    public function test_rejects_filter()
    {
        $this->withTextModel();

        $this->assertNotImplemented(
            (new Request)
                ->path('/texts')
                ->filter("a eq 'b'")
        );
    }
}