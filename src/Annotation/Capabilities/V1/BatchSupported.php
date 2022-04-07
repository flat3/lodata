<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\Boolean;

/**
 * Batch Supported
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class BatchSupported extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.BatchSupported');
        $this->value = new Boolean(true);
    }
}