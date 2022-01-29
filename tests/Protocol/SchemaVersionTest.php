<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class SchemaVersionTest extends TestCase
{
    public function test_schema_version_star()
    {
        $this->assertResponseSnapshot(
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

