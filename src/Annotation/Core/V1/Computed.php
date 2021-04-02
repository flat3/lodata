<?php

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

class Computed extends Annotation
{
    protected $name = 'Org.OData.Core.V1.Computed';

    public function __construct(bool $isComputed = true)
    {
        $this->value = new Boolean($isComputed);
    }
}