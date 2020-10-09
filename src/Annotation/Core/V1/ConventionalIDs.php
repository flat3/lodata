<?php

namespace Flat3\OData\Annotation\Core\V1;

use Flat3\OData\Annotation;
use Flat3\OData\Type\Boolean;

class ConventionalIDs extends Annotation
{
    protected $name = 'Org.OData.Core.V1.ConventionalIDs';

    public function __construct($value)
    {
        $this->type = new Boolean($value);
    }
}