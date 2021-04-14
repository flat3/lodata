<?php

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\String_;

/**
 * LongDescription
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class LongDescription extends Annotation
{
    protected $name = 'Org.OData.Core.V1.LongDescription';

    public function __construct(string $longDescription)
    {
        $this->value = new String_($longDescription);
    }
}