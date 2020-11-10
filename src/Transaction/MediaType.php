<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Exception\Protocol\NotAcceptableException;

/**
 * Media Type
 * @link https://tools.ietf.org/html/rfc2045
 * @package Flat3\Lodata\Transaction
 */
class MediaType
{
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
     * Generate a new media type instance
     * @return static
     */
    public static function factory(): self
    {
        return new self();
    }

    /**
     * Negotiate the value of the type based on the provided options
     * @param  string  $requestedTypes
     * @return $this
     */
    public function negotiate(string $requestedTypes): MediaType
    {
        $types = [];

        foreach (explode(',', $requestedTypes) as $type) {
            $types[] = MediaType::factory()->parse($type);
        }

        // Order by priority
        usort($types, function (MediaType $a, MediaType $b) {
            return $b->getParameter('q') <=> $a->getParameter('q');
        });

        foreach ($types as $type) {
            // Reject formats with unknown format parameters
            if (array_diff($type->getParameterKeys(), [
                'IEEE754Compatible',
                'odata.metadata',
                'odata.streaming',
                'charset',
                'q',
            ])) {
                continue;
            }

            if ($type->getSubtype() !== '*' && $type->getSubtype() !== $this->getSubtype()) {
                continue;
            }

            foreach ($this->getParameterKeys() as $parameterKey) {
                $parameterValue = $type->getParameter($parameterKey);

                if ($parameterValue) {
                    $this->setParameter($parameterKey, $parameterValue);
                } else {
                    $this->dropParameter($parameterKey);
                }
            }

            return $this;
        }

        throw new NotAcceptableException(
            'unsupported_content_type',
            'This route does not support the requested content type, unsupported parameters may have been supplied'
        );
    }

    /**
     * Parse media type
     * @param string $type
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
     * Get all parameter keys in the media type
     * @return array
     */
    public function getParameterKeys()
    {
        return array_keys($this->parameter->getParameters());
    }

    /**
     * Get the subtype
     * @return mixed
     */
    public function getSubtype()
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
     * @internal
     */
    public function toString()
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
