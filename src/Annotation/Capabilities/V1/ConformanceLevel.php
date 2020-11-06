<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\EnumerationType;

class ConformanceLevel extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.ConformanceLevel';

    public function __construct()
    {
        $type = new EnumerationType('Org.OData.Capabilities.V1.ConformanceLevelType');

        $type[] = 'Minimal';
        $type[] = 'Intermediate';
        $type[] = 'Advanced';

        $this->value = $type->instance('Advanced');
    }
}