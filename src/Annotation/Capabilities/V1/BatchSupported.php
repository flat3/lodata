<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

/**
 * Batch Supported
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class BatchSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.BatchSupported';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}