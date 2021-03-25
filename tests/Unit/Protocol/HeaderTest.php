<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class HeaderTest extends TestCase
{
    public function test_rejects_isolation_header()
    {
        $this->assertPreconditionFailed(
            Request::factory()
                ->path('/')
                ->header('isolation', 'snapshot')
        );
    }

    public function test_rejects_odata_isolation_header()
    {
        $this->assertPreconditionFailed(
            Request::factory()
                ->path('/')
                ->header('OData-Isolation', 'snapshot')
        );
    }
}
