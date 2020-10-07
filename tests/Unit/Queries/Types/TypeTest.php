<?php

namespace Flat3\OData\Tests\Unit\Queries\Types;

use Flat3\OData\Tests\TestCase;

abstract class TypeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }
}