<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Enum;

class ConformanceLevel extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.ConformanceLevel';

    public function __construct()
    {
        $this->value = new Enum();
        $this->value->add('Org.OData.Capabilities.V1.ConformanceLevelType/Minimal');
        $this->value->add('Org.OData.Capabilities.V1.ConformanceLevelType/Intermediate');
        $this->value->add('Org.OData.Capabilities.V1.ConformanceLevelType/Advanced');
        $this->value->set('Org.OData.Capabilities.V1.ConformanceLevelType/Advanced');
    }
}