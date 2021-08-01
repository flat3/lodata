<?php

declare(strict_types=1);

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
     * URI of the service document, without the xml/json suffix
     * @var string $uri URI
     */
    protected $uri;

    /**
     * @var string $namespace Namespace
     */
    protected $namespace;

    /**
     * Append this reference to the provided XML element
     * @param  SimpleXMLElement  $schema  Schema
     * @return $this
     */
    public function appendXml(SimpleXMLElement $schema): self
    {
        $reference = $schema->addChild('Reference');
        $reference->addAttribute('Uri', $this->uri.'.xml');
        $include = $reference->addChild('Include');
        $include->addAttribute('Namespace', $this->namespace);

        return $this;
    }

    /**
     * Append this reference to the provided JSON class
     * @param  object  $json
     * @return $this
     */
    public function appendJson(object $json): self
    {
        $json->{'$Reference'}[$this->uri.'.json'] = [
            '$Include' => [
                [
                    '$Namespace' => $this->namespace,
                ]
            ]
        ];

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->namespace;
    }
}