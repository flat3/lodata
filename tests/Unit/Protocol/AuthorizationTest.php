<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
        config(['lodata.authorization' => true]);
        config(['lodata.readonly' => false]);
    }

    public function test_no_authorization()
    {
        config(['lodata.authorization' => false]);

        $this->assertMetadataResponse(
            Request::factory()
                ->path('/flights')
        );
    }

    public function gateAssertion()
    {
        $this->gateMock->andReturnUsing(function ($lodata, Gate $gate) {
            $this->assertMatchesSnapshot([
                $lodata,
                $gate->getAccess(),
                $gate->getResource()->getResourceUrl($gate->getTransaction())
            ]);

            return true;
        });
    }

    public function test_query_denied()
    {
        $this->gateAssertion();
        $this->assertForbidden(
            Request::factory()
                ->path('/flights')
        );
    }

    public function test_read_denied()
    {
        $this->gateAssertion();
        $this->assertForbidden(
            Request::factory()
                ->path('/flights(1)')
        );
    }

    public function test_delete_denied()
    {
        $this->gateAssertion();
        $this->assertForbidden(
            Request::factory()
                ->delete()
                ->path('/flights(1)')
        );
    }

    public function test_create_denied()
    {
        $this->gateAssertion();
        $this->assertForbidden(
            Request::factory()
                ->post()
                ->path('/flights')
        );
    }

    public function test_update_denied()
    {
        $this->gateAssertion();
        $this->assertForbidden(
            Request::factory()
                ->patch()
                ->path('/flights(1)')
        );
    }
}

