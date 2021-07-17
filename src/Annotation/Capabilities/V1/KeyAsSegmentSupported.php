<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

/**
 * Key-as-segment support
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class KeyAsSegmentSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.KeyAsSegmentSupported';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}