<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class MaxPageSizeTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_uses_maxpagesize_preference()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->entitySetPath)
                ->header('Prefer', 'maxpagesize=1')
        );
    }

    public function test_uses_odata_maxpagesize_preference()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->entitySetPath)
                ->header('Prefer', 'odata.maxpagesize=1')
        );
    }

    public function test_unlimited()
    {
        config([
            'lodata.pagination.default' => null,
            'lodata.pagination.max' => null
        ]);

        $response = $this->req(
            (new Request)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 5);
    }

    public function test_unlimited_uses_top()
    {
        config([
            'lodata.pagination.default' => null,
            'lodata.pagination.max' => null
        ]);

        $response = $this->req(
            (new Request)
                ->top(2)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 2);
    }

    public function test_unlimited_uses_maxpagesize()
    {
        config([
            'lodata.pagination.default' => null,
            'lodata.pagination.max' => null
        ]);

        $response = $this->req(
            (new Request)
                ->preference('maxpagesize', 2)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 2);
    }

    public function test_limits_to_default_if_unspecified()
    {
        config(['lodata.pagination.default' => 1]);

        $response = $this->req(
            (new Request)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 1);
    }

    public function test_limits_to_max_if_unspecified()
    {
        config(['lodata.pagination.max' => 1]);

        $response = $this->req(
            (new Request)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 1);
    }

    public function test_preference_overrides_default()
    {
        config(['lodata.pagination.default' => 1]);

        $response = $this->req(
            (new Request)
                ->preference('maxpagesize', 2)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 2);
    }

    public function test_preference_does_not_override_max()
    {
        config(['lodata.pagination.max' => 1]);

        $response = $this->req(
            (new Request)
                ->preference('maxpagesize', 2)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 1);
    }

    public function test_top_overrides_default()
    {
        config(['lodata.pagination.default' => 1]);

        $response = $this->req(
            (new Request)
                ->top(2)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 2);
    }

    public function test_top_does_not_override_max()
    {
        config(['lodata.pagination.max' => 1]);

        $response = $this->req(
            (new Request)
                ->top(2)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 1);
    }

    public function test_top_overrides_maxpagesize()
    {
        $response = $this->req(
            (new Request)
                ->top(1)
                ->preference('maxpagesize', 2)
                ->path($this->entitySetPath)
        );

        $this->assertResponseHeaderSnapshot($response);
        $this->assertResultCount($response, 1);
    }
}
