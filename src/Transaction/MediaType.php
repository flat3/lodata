<?php

namespace Flat3\OData\Transaction;

use Flat3\OData\Exception\Protocol\NotAcceptableException;

class MediaType
{
    protected $original;
    protected $type;
    protected $subtype;
    protected $tree;
    protected $suffix;

    /** @var ParameterList $parameters */
    protected $parameters;

    public static function factory(): self
    {
        return new self();
    }

    public static function negotiate(string $requestedTypes, string $requiredType): MediaType
    {
        $types = [];
        foreach (explode(',', $requestedTypes) as $type) {
            $types[] = MediaType::factory()->parse($type);
        }

        usort($types, function (MediaType $a, MediaType $b) {
            return $b->getParameter('q') <=> $a->getParameter('q');
        });

        $requiredType = MediaType::factory()->parse($requiredType);

        foreach ($types as $type) {
            if ($type->getSubtype() === '*' || $type->getSubtype() === $requiredType->getSubtype()) {
                foreach ($requiredType->getParameterKeys() as $parameterKey) {
                    $parameterValue = $type->getParameter($parameterKey);
                    if ($parameterValue) {
                        $requiredType->setParameter($parameterKey, $parameterValue);
                    } else {
                        $requiredType->dropParameter($parameterKey);
                    }
                }
                return $requiredType;
            }
        }

        throw new NotAcceptableException(
            'unsupported_content_type',
            'This route does not support the requested content type'
        );
    }

    public function parse($type): self
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

        return $this;
    }

    public function getOriginal(): string
    {
        return $this->original;
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

    public function setParameter(string $key, string $value): self
    {
        $this->parameters->addParameter($key, $value);
        return $this;
    }

    public function dropParameter(string $key): self
    {
        $this->parameters->dropParameter($key);
        return $this;
    }

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

        if ($this->parameters->getParameters()) {
            $type .= ';'.$this->parameters;
        }

        return $type;
    }
}
