<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Interfaces\RequestInterface;
use Flat3\Lodata\NavigationProperty;
use Illuminate\Http\Request;

/**
 * Navigation Request
 * @package Flat3\Lodata\Transaction
 */
class NavigationRequest implements RequestInterface
{
    /**
     * Navigation property attached to this request
     * @var NavigationProperty $navigationProperty
     */
    protected $navigationProperty;

    /**
     * Base request
     * @var RequestInterface $request
     */
    public $request;

    /**
     * Navigation path of this request
     * @var string $basePath
     */
    protected $basePath;

    /**
     * Body content
     * @var string $content
     */
    protected $content;

    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * Set the outer request that generated this request
     * @param  RequestInterface  $request  Request
     * @return $this
     */
    public function setOuterRequest(RequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the query string on this request
     * @param  string  $queryString  Query string
     * @return $this
     */
    public function setQueryString(string $queryString): self
    {
        $lexer = new Lexer($queryString);
        $parameters = $lexer->splitSemicolonSeparatedQueryString();
        $this->request->query->replace($parameters);

        return $this;
    }

    /**
     * Get the path of this request
     * @return string Path
     */
    public function path()
    {
        return $this->basePath;
    }

    /**
     * Set the path of this request
     * @param  string  $path  Path
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->basePath = $path;

        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content ?: $this->request->getContent();
    }

    /**
     * Set the navigation property for this request
     * @param  NavigationProperty  $navigationProperty  Navigation property
     * @return $this
     */
    public function setNavigationProperty(NavigationProperty $navigationProperty): self
    {
        $this->navigationProperty = $navigationProperty;

        return $this;
    }

    /**
     * Get the navigation property for this request
     * @return NavigationProperty
     */
    public function getNavigationProperty(): NavigationProperty
    {
        return $this->navigationProperty;
    }

    /**
     * @return string
     */
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
