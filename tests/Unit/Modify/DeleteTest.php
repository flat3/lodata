<?php

namespace Flat3\Lodata\Tests\Unit\Modify;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function tearDown(): void
    {
        $this->assertDatabaseSnapshot();
        parent::tearDown();
    }

    public function test_delete()
    {
        $this->assertNoContent(
            (new Request)
                ->path('/flights(1)')
                ->delete()
        );
    }

    public function test_delete_ref()
    {
        $this->assertNoContent(
            (new Request)
                ->path('/flights(1)/$ref')
                ->delete()
        );
    }

    public function test_delete_not_found()
    {
        $this->assertNotFound(
            (new Request)
                ->path('/flights(999)')
                ->delete()
        );
    }

    public function test_validate_etag()
    {
        $response = $this->req(
            (new Request)
                ->path('/flights(1)')
        );

        $etag = $response->headers->get('etag');

        $this->assertNoContent(
            (new Request)
                ->path('/flights(1)')
                ->header('if-match', $etag)
                ->delete()
        );

        $this->assertPreconditionFailed(
            (new Request)
                ->path('/flights(2)')
                ->header('if-match', $etag)
                ->delete()
        );
    }
}