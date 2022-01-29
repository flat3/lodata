<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class IsolationTest extends TestCase
{
    public function test_rejects_isolation_header()
    {
        $this->assertPreconditionFailed(
            (new Request)
                ->path('/')
                ->header('isolation', 'snapshot')
        );
    }

    public function test_rejects_odata_isolation_header()
    {
        $this->assertPreconditionFailed(
            (new Request)
                ->path('/')
                ->header('OData-Isolation', 'snapshot')
        );
    }
}
