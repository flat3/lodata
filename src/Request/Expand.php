<?php

namespace Flat3\OData\Request;

use Illuminate\Http\Request;
use Flat3\OData\Attribute\ParameterList;
use Flat3\OData\Option\Count;
use Flat3\OData\Option\Filter;
use Flat3\OData\Option\OrderBy;
use Flat3\OData\Option\Search;
use Flat3\OData\Option\Skip;
use Flat3\OData\Option\Top;
use Flat3\OData\Property\Navigation;

class Expand extends Request
{
    public const options = [
        Filter::class,
        OrderBy::class,
        Skip::class,
        Top::class,
        Count::class,
        Search::class,
    ];

    /** @var Navigation $navigationProperty */
    protected $navigationProperty;

    /** @var ParameterList $options */
    protected $options;

    public function __construct(Navigation $navigationProperty, ?string $options = '')
    {
        $this->navigationProperty = $navigationProperty;
        $this->options = new ParameterList($options, ';');
    }

    public function getHeader($key): ?string
    {
        return null;
    }

    public function getMethod(): string
    {
        return Request::METHOD_GET;
    }

    public function getNavigationProperty(): Navigation
    {
        return $this->navigationProperty;
    }

    public function getQueryParams(): array
    {
        return $this->options->getParameters();
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
