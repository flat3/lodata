<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\Model;
use Flat3\OData\PrimitiveType;
use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\Data\TextModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\Int32;

class ActionTest extends TestCase
{
    use FlightModel;
    use TextModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
        $this->withTextModel();
    }

    public function test_callback()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/exa1()')
        );
    }

    public function test_callback_entity()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/exa2()')
        );
    }

    public function test_no_composition()
    {
        Model::action('textv1')
            ->setCallback(function (): Int32 {
                return new Int32(3);
            });

        $this->assertBadRequest(
            Request::factory()
                ->path('/textv1()/$value')
        );
    }

    public function test_void_callback()
    {
        Model::action('textv1')
            ->setCallback(function (): void {
            });

        $this->assertNoContent(
            Request::factory()
                ->path('/textv1()')
        );
    }

    public function test_default_null_callback()
    {
        Model::action('textv1')
            ->setCallback(function () {
            });

        $this->assertNoContent(
            Request::factory()
                ->path('/textv1()')
        );
    }

    public function test_explicit_null_callback()
    {
        Model::action('textv1')
            ->setCallback(function () {
                return null;
            });

        $this->assertNoContent(
            Request::factory()
                ->path('/textv1()')
        );
    }
}