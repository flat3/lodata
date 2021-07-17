<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

/**
 * Asynchronous Requests Supported
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class AsynchronousRequestsSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.AsynchronousRequestsSupported';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}