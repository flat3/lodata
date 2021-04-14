<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

/**
 * Query segment support
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class QuerySegmentSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.QuerySegmentSupported';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}