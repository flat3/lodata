<?php

namespace Flat3\Lodata\Annotation;

use SimpleXMLElement;

/**
 * Reference to an external CSDL document
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Reference
 * @package Flat3\Lodata\Annotation
 */
class Reference
{
    /**
     * @var string $uri URI
     * @internal
     */
    protected $uri;

    /**
     * @var string $namespace Namespace
     * @internal
     */
    protected $namespace;

    /**
     * Append this annotation to the provided schema element
     * @param  SimpleXMLElement  $schema  Schema
     * @return $this
     */
    public function append(SimpleXMLElement $schema): self
    {
        $reference = $schema->addChild('Reference');
        $reference->addAttribute('Uri', $this->uri);
        $include = $reference->addChild('Include');
        $include->addAttribute('Namespace', $this->namespace);

        return $this;
    }

    /**
     * @return string
     * @internal
     */
    public function __toString()
    {
        return $this->namespace;
    }
}