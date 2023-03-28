<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Support\Facades\Gate as LaravelGate;

class AuthorizationTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function setUp(): void
    {
        parent::setUp();

        config(['lodata.authorization' => true]);
    }

    public function test_no_authorization()
    {
        config(['lodata.authorization' => false]);

        $this->assertResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );
    }

    public function gateAssertion()
    {
        LaravelGate::shouldReceive('check')->andReturnUsing(function (string $lodata, Gate $gate) {
            $this->assertMatchesSnapshot([
                $lodata,
                $gate->getAccess(),
                $gate->getResource()->getResourceUrl($gate->getTransaction())
            ]);

            return false;
        });
    }

    public function test_query_denied()
    {
        $this->gateAssertion();
        $this->assertUnauthorized(
            (new Request)
                ->path($this->entitySetPath)
        );
    }

    public function test_read_denied()
    {
        $this->gateAssertion();
        $this->assertUnauthorized(
            (new Request)
                ->path($this->entityPath)
        );
    }

    public function test_delete_denied()
    {
        $this->gateAssertion();
        $this->assertUnauthorized(
            (new Request)
                ->delete()
                ->path($this->entityPath)
        );
    }

    public function test_create_denied()
    {
        $this->gateAssertion();
        $this->assertUnauthorized(
            (new Request)
                ->post()
                ->body([])
                ->path($this->entitySetPath)
        );
    }

    public function test_update_denied()
    {
        $this->gateAssertion();
        $this->assertUnauthorized(
            (new Request)
                ->patch()
                ->body([])
                ->path($this->entityPath)
        );
    }
}

