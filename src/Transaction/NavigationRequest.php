<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Interfaces\RequestInterface;
use Flat3\Lodata\NavigationProperty;
use Illuminate\Http\Request;

class NavigationRequest implements RequestInterface
{
    /** @var NavigationProperty $navigationProperty */
    protected $navigationProperty;

    /** @var Request $request */
    public $request;

    protected $basePath;

    public function __construct()
    {
        $this->request = new Request();
    }

    public function setOuterRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function setQueryString(string $queryString): self
    {
        $lexer = new Lexer($queryString);
        $parameters = $lexer->splitCommaSeparatedQueryString();
        $this->request->query->replace($parameters);

        return $this;
    }

    public function path()
    {
        return $this->basePath;
    }

    public function setPath(string $path): self
    {
        $this->basePath = $path;

        return $this;
    }

    public function setNavigationProperty(NavigationProperty $navigationProperty): self
    {
        $this->navigationProperty = $navigationProperty;

        return $this;
    }

    public function getNavigationProperty(): NavigationProperty
    {
        return $this->navigationProperty;
    }

    public function __toString()
    {
        return $this->path();
    }

    public function __get($parameter)
    {
        return $this->request->$parameter;
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->request, $method], $parameters);
    }
}
