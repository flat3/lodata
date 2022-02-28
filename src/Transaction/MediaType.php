<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

/**
 * Media Type
 * @link https://tools.ietf.org/html/rfc2045
 * @package Flat3\Lodata\Transaction
 */
class MediaType
{
    const xml = 'application/xml';
    const json = 'application/json';
    const text = 'text/plain';
    const multipartMixed = 'multipart/mixed';
    const any = '*/*';

    protected $original;
    protected $type;
    protected $subtype;
    protected $tree;
    protected $suffix;

    /**
     * Parameters attached to this media type
     * @var Parameter $parameter
     */
    protected $parameter;

    /**
     * Parse media type
     * @param  string  $type
     * @return $this
     */
    public function parse(string $type): self
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
        $this->parameter = new Parameter();
        $this->parameter->parse($matches['parameters'] ?? '');

        return $this;
    }

    /**
     * Get the original type string
     * @return string
     */
    public function getOriginal(): string
    {
        return $this->original;
    }

    /**
     * Get a parameter from the media type
     * @param  string  $key
     * @return string|null
     */
    public function getParameter(string $key): ?string
    {
        return $this->parameter->getParameter($key);
    }

    /**
     * Check whether the type has the provided parameter
     * @param  string  $key  Parameter
     * @return bool
     */
    public function hasParameter(string $key): bool
    {
        return null !== $this->getParameter($key);
    }

    /**
     * Get all parameter keys in the media type
     * @return array
     */
    public function getParameterKeys()
    {
        return array_keys($this->parameter->getParameters());
    }

    /**
     * Get the type
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the type with subtype
     * @return string
     */
    public function getFullType(): string
    {
        return $this->type.'/'.$this->subtype;
    }

    /**
     * Get the subtype
     * @return string
     */
    public function getSubtype(): string
    {
        return $this->subtype;
    }

    /**
     * Set a parameter on the type
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    public function setParameter(string $key, string $value): self
    {
        $this->parameter->addParameter($key, $value);
        return $this;
    }

    /**
     * Drop a parameter
     * @param  string  $key
     * @return $this
     */
    public function dropParameter(string $key): self
    {
        $this->parameter->dropParameter($key);
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $type = $this->type.'/';

        if ($this->tree) {
            $type .= $this->tree.'.';
        }

        $type .= $this->subtype;

        if ($this->suffix) {
            $type .= '+'.$this->suffix;
        }

        if ($this->parameter->getParameters()) {
            $type .= ';'.$this->parameter;
        }

        return $type;
    }
}
