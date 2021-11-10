<?php

namespace Flat3\Lodata\Tests;

use Flat3\Lodata\Helper\Url;

class Request
{
    public $headers = [];
    public $path = '/odata';
    public $query = [];
    public $body = null;
    public $method = \Illuminate\Http\Request::METHOD_GET;

    public function __construct()
    {
        $this->json();
    }

    public function header($key, $value): Request
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function unsetHeader($key): self
    {
        unset($this->headers[$key]);

        return $this;
    }

    public function query($key, $value): Request
    {
        $this->query[$key] = $value;
        return $this;
    }

    public function metadata($type): Request
    {
        $this->accept('application/json;odata.metadata='.$type);
        return $this;
    }

    public function preference($key, $value): Request
    {
        $this->header('prefer', $key.'='.$value);
        return $this;
    }

    public function path($path, $withPrefix = true): Request
    {
        $this->path = $path;

        if ($withPrefix) {
            $this->path = '/odata'.$this->path;
        }

        return $this;
    }

    public function filter($filter): Request
    {
        $this->query('$filter', $filter);
        return $this;
    }

    public function select($select): Request
    {
        $this->query('$select', $select);
        return $this;
    }

    public function uri(): string
    {
        return Url::http_build_url([
            'query' => http_build_query($this->query),
            'path' => $this->path,
        ]);
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function method($method): self
    {
        $this->method = $method;
        return $this;
    }

    public function post(): self
    {
        return $this->method(\Illuminate\Http\Request::METHOD_POST);
    }

    public function multipart(string $body): self
    {
        $this->header('accept', 'multipart/mixed');
        $this->body = str_replace("\n", "\r\n", $body);

        return $this;
    }

    public function body($body): self
    {
        if (is_array($body)) {
            $this->header('content-type', 'application/json');
            $body = json_encode($body, JSON_UNESCAPED_SLASHES);
        }

        $this->body = $body;
        return $this;
    }

    public function patch(): self
    {
        return $this->method(\Illuminate\Http\Request::METHOD_PATCH);
    }

    public function put(): self
    {
        return $this->method(\Illuminate\Http\Request::METHOD_PUT);
    }

    public function delete(): self
    {
        return $this->method(\Illuminate\Http\Request::METHOD_DELETE);
    }

    public function xml(): Request
    {
        $this->header('accept', 'application/xml');
        return $this;
    }

    public function json(): self
    {
        $this->header('accept', 'application/json');
        return $this;
    }

    public function text(): Request
    {
        $this->header('accept', 'text/plain');
        return $this;
    }

    public function accept($accept): Request
    {
        $this->header('accept', $accept);
        return $this;
    }
}
