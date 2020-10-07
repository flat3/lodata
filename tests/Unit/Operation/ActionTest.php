<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\Model;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\Int32;

class ActionTest extends TestCase
{
    public function test_callback()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exa1()')
        );
    }

    public function test_callback_entity()
    {
        $this->withFlightModel();

        $this->assertNotFound(
            Request::factory()
                ->path('/exa2()')
        );
    }

    public function test_no_composition()
    {
        $this->withTextModel();

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
        $this->withTextModel();

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
        $this->withTextModel();

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
        $this->withTextModel();

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