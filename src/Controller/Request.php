<?php

namespace Flat3\Lodata\Controller;

use Illuminate\Http\Request as IlluminateRequest;

class Request extends IlluminateRequest
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(IlluminateRequest $request)
    {
        $this->method = $request->method;
        $this->headers = $request->headers;
        $this->query = $request->query;
        $this->content = $request->content;
        $this->server = $request->server;
    }
}
