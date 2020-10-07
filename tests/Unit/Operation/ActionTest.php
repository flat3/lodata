<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\Model;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\Int32;
use Flat3\OData\Type\String_;

class ActionTest extends TestCase
{
    public function test_callback()
    {
        Model::action('exa1')
            ->setCallback(function (): String_ {
                return String_::factory('hello');
            });

        $this->assertMethodNotAllowed(
            Request::factory()
                ->path('/exa1()')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->post()
                ->path('/exa1()')
        );
    }

    public function test_service_document()
    {
        Model::action('exa1')
            ->setCallback(function (): String_ {
                return String_::factory('hello');
            });

        $this->assertJsonResponse(
            Request::factory()
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
                ->post()
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
                ->post()
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
                ->post()
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
                ->post()
                ->path('/textv1()')
        );
    }
}