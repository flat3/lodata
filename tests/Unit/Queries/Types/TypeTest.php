<?php

namespace Flat3\OData\Tests\Unit\Queries\Types;

use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\TestCase;

abstract class TypeTest extends TestCase {
    use FlightModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }
}