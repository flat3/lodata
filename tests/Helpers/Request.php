<?php

namespace Flat3\Lodata\Tests\Helpers;

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

    public function header(string $key, $value): Request
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function unsetHeader(string $key): self
    {
        unset($this->headers[$key]);

        return $this;
    }

    public function query(string $key, string $value): Request
    {
        $this->query[$key] = $value;
        return $this;
    }

    public function metadata(string $type): Request
    {
        $this->accept('application/json;odata.metadata='.$type);
        return $this;
    }

    public function preference(string $key, string $value): Request
    {
        $this->header('prefer', $key.'='.$value);
        return $this;
    }

    public function path(string $path, bool $withPrefix = true): Request
    {
        $this->path = $path;

        if ($withPrefix) {
            $this->path = '/odata'.$this->path;
        }

        return $this;
    }

    public function filter(string $filter): Request
    {
        $this->query('$filter', $filter);
        return $this;
    }

    public function select(string $select): Request
    {
        $this->query('$select', $select);
        return $this;
    }

    public function orderby(string $orderby): Request
    {
        $this->query('$orderby', $orderby);
        return $this;
    }

    public function compute(string $compute): Request
    {
        $this->query('$compute', $compute);
        return $this;
    }

    public function top(string $top): Request
    {
        $this->query('$top', $top);
        return $this;
    }

    public function skip(string $skip): Request
    {
        $this->query('$skip', $skip);
        return $this;
    }

    public function skiptoken(string $skiptoken): Request
    {
        $this->query('$skiptoken', $skiptoken);
        return $this;
    }

    public function search(string $search): Request
    {
        $this->query('$search', $search);
        return $this;
    }

    public function index(string $index): Request
    {
        $this->query('$index', $index);
        return $this;
    }

    public function expand(string $expand): Request
    {
        $this->query('$expand', $expand);
        return $this;
    }

    public function format(string $format): Request
    {
        $this->query('$format', $format);
        return $this;
    }

    public function count(string $count): Request
    {
        $this->query('$count', $count);
        return $this;
    }

    public function id(string $id): Request
    {
        $this->query('$id', $id);
        return $this;
    }

    public function apply(string $apply): Request
    {
        $this->query('$apply', $apply);
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

    public function method(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function post(): self
    {
        return $this->method(\Illuminate\Http\Request::METHOD_POST);
    }

    public function multipart(string $body, bool $convertNewlines = true): self
    {
        $this->header('accept', 'multipart/mixed');
        $this->body = $convertNewlines ? str_replace("\n", "\r\n", $body) : $body;

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
        $this->header('accept', 'text/plain;charset=utf-8');
        return $this;
    }

    public function accept(string $accept): Request
    {
        $this->header('accept', $accept);
        return $this;
    }
}
