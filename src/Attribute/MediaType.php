<?php

namespace Flat3\OData\Attribute;

class MediaType
{
    protected $original;
    protected $type;
    protected $subtype;
    protected $tree;
    protected $suffix;

    /** @var ParameterList $parameters */
    protected $parameters;

    public function __construct($type)
    {
        $this->original = $type;

        // type "/" [tree "."] subtype ["+" suffix] *[";" parameter]

        preg_match(
            ':^'.

            '(?P<type>[*\w]+)'. // type

            '/'. // /

            '('.
            '(?P<tree>\w+)\.'. // tree
            ')?'.

            '(?P<subtype>[*\w\-.]+)'. // subtype

            '(\+'.
            '(?P<suffix>[.\-\w]+)'. // suffix
            ')?'.

            ';?'.

            '(?P<parameters>[^,]*)?'. // parameters

            ':',
            $type,
            $matches
        );

        $this->type = $matches['type'] ?? '*';
        $this->subtype = $matches['subtype'] ?? '*';
        $this->tree = $matches['tree'] ?? '';
        $this->suffix = $matches['suffix'] ?? '';
        $this->parameters = new ParameterList($matches['parameters'] ?? '', ';');
    }

    public function getParameter(string $key): ?string
    {
        return $this->parameters->getParameter($key);
    }

    public function getParameterKeys()
    {
        return array_keys($this->parameters->getParameters());
    }

    public function getSubtype()
    {
        return $this->subtype;
    }
}
