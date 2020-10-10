<?php

namespace Flat3\OData\Annotation\Capabilities\V1;

use Flat3\OData\Annotation;
use Flat3\OData\Type\Enum;

class ConformanceLevel extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.ConformanceLevel';

    public function __construct($value)
    {
        $this->type = new Enum();
        $this->type->add('Org.OData.Capabilities.V1.ConformanceLevelType/Minimal');
        $this->type->add('Org.OData.Capabilities.V1.ConformanceLevelType/Intermediate');
        $this->type->add('Org.OData.Capabilities.V1.ConformanceLevelType/Advanced');
        $this->type->set($value)->seal();
    }
}