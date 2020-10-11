<?php

namespace Flat3\OData\Annotation\Core\V1;

use Flat3\OData\Annotation;
use Flat3\OData\Type\Boolean;

class DefaultNamespace extends Annotation
{
    protected $name = 'Org.OData.Core.V1.DefaultNamespace';

    public function __construct()
    {
        $this->type = new Boolean(true);
        $this->type->seal();
    }
}