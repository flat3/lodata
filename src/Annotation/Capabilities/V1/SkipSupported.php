<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

/**
 * Skip Supported
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SkipSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.SkipSupported';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}