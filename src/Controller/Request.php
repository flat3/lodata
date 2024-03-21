<?php

declare(strict_types=1);

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Interfaces\RequestInterface;
use Illuminate\Http\Request as IlluminateRequest;

/**
 * Request
 * @package Flat3\Lodata\Controller
 */
class Request extends IlluminateRequest implements RequestInterface
{
    /**
     * Set the request path
     * @param  string  $path  Request path
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->pathInfo = $path;

        return $this;
    }

    /**
     * Set the request content
     * @param  mixed  $content  Request content
     * @return $this
     */
    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Implement x-http-method tunnelling
     * @return string
     */
    public function getMethod(): string
    {
        $method = parent::getMethod();

        if ($method !== self::METHOD_POST) {
            return $method;
        }

        $x_http_method = $this->headers->get('x-http-method');

        if ($x_http_method) {
            return strtoupper($x_http_method);
        }

        return $method;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(IlluminateRequest $request)
    {
        $this->method = $request->getRealMethod();
        $this->headers = $request->headers;
        $this->query = $request->query;
        $this->content = $request->content;
        $this->server = $request->server;
        $this->request = $request->request;
        $this->attributes = $request->attributes;
        $this->files = $request->files;
    }
}
