<?php

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

class DereferencableIDs extends Annotation
{
    protected $name = 'Org.OData.Core.V1.DereferencableIDs';

    public function __construct()
    {
        $this->type = new Boolean(true);
        $this->type->seal();
    }
}