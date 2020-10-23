<?php

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

class ConventionalIDs extends Annotation
{
    protected $name = 'Org.OData.Core.V1.ConventionalIDs';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}