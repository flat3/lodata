<?php

namespace Flat3\OData\Tests\Unit\Options;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class SchemaVersionTest extends TestCase
{
    public function test_schema_version_star()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->query('$schemaversion', '*')
        );
    }

    public function test_schema_version_invalid()
    {
        $this->assertNotFound(
            Request::factory()
                ->query('$schemaversion', '1')
        );
    }
}

