<?php

namespace Flat3\OData\Annotation\Org\OData\Core\V1;

use Flat3\OData\Annotation;
use Flat3\OData\Type\Boolean;

class DereferencableIDs extends Annotation
{
    protected $name = 'Org.OData.Core.V1.DereferencableIDs';

    public function __construct($value)
    {
        $this->type = new Boolean($value);
    }
}