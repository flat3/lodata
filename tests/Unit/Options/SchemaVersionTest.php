<?php

namespace Flat3\Lodata\Tests\Unit\Options;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class SchemaVersionTest extends TestCase
{
    public function test_schema_version_star()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->query('$schemaversion', '*')
        );
    }

    public function test_schema_version_invalid()
    {
        $this->assertNotFound(
            (new Request)
                ->query('$schemaversion', '1')
        );
    }
}

