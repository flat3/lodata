<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\Boolean;

/**
 * Top Supported
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class TopSupported extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.TopSupported');
        $this->value = new Boolean(true);
    }

    public function setSupported(bool $supported): self
    {
        $this->value->set($supported);

        return $this;
    }

    public function isSupported(): bool
    {
        return true === $this->value->get();
    }
}