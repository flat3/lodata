<?php

namespace Flat3\OData\Annotation\Capabilities\V1;

use Flat3\OData\Annotation;
use Flat3\OData\Type\Boolean;

class AsynchronousRequestsSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.AsynchronousRequestsSupported';

    public function __construct()
    {
        $this->type = new Boolean(true);
        $this->type->seal();
    }
}