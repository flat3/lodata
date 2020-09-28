<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class HeaderTest extends TestCase
{
    public function test_rejects_isolation_header()
    {
        $this->assertPreConditionFailed(
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
