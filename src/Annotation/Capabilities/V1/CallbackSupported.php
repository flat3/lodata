<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Collection;

/**
 * Callback Supported
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class CallbackSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.CallbackSupported';

    public function __construct()
    {
        $this->value = new Collection();
    }
}