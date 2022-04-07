<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Helper\Identifier;

/**
 * Conformance Level
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class ConformanceLevel extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.ConformanceLevel');
        $type = new EnumerationType('Org.OData.Capabilities.V1.ConformanceLevelType');

        $type[] = 'Minimal';
        $type[] = 'Intermediate';
        $type[] = 'Advanced';

        $this->value = $type->instance('Advanced');
    }
}