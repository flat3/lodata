<?php

namespace Flat3\OData\Tests;

class Request
{
    public $headers = [];
    public $path = '/';
    public $query = [];

    public static function factory()
    {
        $request = new self();
        $request->header('accept', 'application/json');
        return $request;
    }

    public function query($key, $value)
    {
        $this->query[$key] = $value;
        return $this;
    }

    public function metadata($type)
    {
        return $this;
    }

    public function preference($key, $value)
    {
        return $this;
    }

    public function header($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function path($path)
    {
        $this->path = $path;
        return $this;
    }

    public function uri()
    {
        return http_build_url([
            'query' => http_build_query($this->query),
            'path' => $this->path,
        ]);
    }

    public function headers()
    {
        return $this->headers;
    }

    public function xml()
    {
        $this->header('accept', 'application/xml');
        return $this;
    }

    public function text()
    {
        $this->header('accept', 'text/plain');
        return $this;
    }

    public function accept($accept)
    {
        $this->header('accept', $accept);
        return $this;
    }
}
