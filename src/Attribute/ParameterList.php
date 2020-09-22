<?php

namespace Flat3\OData\Attribute;

class ParameterList
{
    private $parameters = [];

    public function __construct(string $value = null, string $delimiter = ',')
    {
        if (!$value) {
            return;
        }

        $parameters = array_filter(array_map('trim', explode($delimiter, $value)));

        foreach ($parameters as $parameter) {
            if (strpos($parameter, '=') === false) {
                $key = $parameter;
                $value = '';
            } else {
                list($key, $value) = explode('=', $parameter);
            }

            $this->addParameter($key, $value);
        }
    }

    public function addParameter($key, $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function getParameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function __toString()
    {
        return http_build_query($this->parameters, '', ';');
    }
}
