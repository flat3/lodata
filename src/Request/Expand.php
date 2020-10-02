<?php

namespace Flat3\OData\Request;

use Flat3\OData\Attribute\ParameterList;
use Flat3\OData\Property\Navigation;
use Illuminate\Http\Request;

class Expand extends Request
{
    /** @var Navigation $navigationProperty */
    protected $navigationProperty;

    /** @var ParameterList $options */
    protected $options;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(Navigation $navigationProperty, ?string $options = '')
    {
        $this->navigationProperty = $navigationProperty;
        $this->options = new ParameterList($options, ';');
    }

    public function getMethod(): string
    {
        return Request::METHOD_GET;
    }

    public function getNavigationProperty(): Navigation
    {
        return $this->navigationProperty;
    }

    public function __toString()
    {
        return $this->getPath();
    }

    public function getPath(): string
    {
        return $this->navigationProperty->getIdentifier()->get();
    }
}
