<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Interfaces\RequestInterface;
use Illuminate\Http\Request as IlluminateRequest;

/**
 * Request
 * @package Flat3\Lodata\Controller
 */
class Request extends IlluminateRequest implements RequestInterface
{
    protected $referenceId;

    public function setReferenceId(string $referenceId): self
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function setPath(string $path): self
    {
        $this->pathInfo = $path;

        return $this;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(IlluminateRequest $request)
    {
        $this->method = $request->getRealMethod();
        $this->headers = $request->headers;
        $this->query = $request->query;
        $this->content = $request->content;
        $this->server = $request->server;
    }
}
