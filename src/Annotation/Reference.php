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
     * The URI SHOULD be a URL that locates the referenced document. If the URI is not dereferencable it
     * SHOULD identify a well-known schema. The URI MAY be absolute or relative URI; relative URLs are
     * relative to the URL of the document containing the reference, or relative to a base URL specified
     * in a format-specific way.
     *
     * @var string $uri URI an absolute or relative URI without extension .xml or .json;
     */
    protected $uri;

    /**
     * @var string $namespace Namespace the namespace of a schema defined in the referenced CSDL document.
     */
    protected $namespace;

    /**
     * @var string $alias Alias a simple identifier that can be used in qualified names instead of the namespace.
     */
    protected $alias;

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
        if (!is_null($this->alias)) {
            $include->addAttribute('Alias', $this->alias);
        }
        return $this;
    }

    /**
     * Append this reference to the provided JSON class
     * @param  object  $json
     * @return $this
     */
    public function appendJson(object $json): self
    {
        $include = [
            '$Namespace' => $this->namespace,
        ];
        if (!is_null($this->alias)) {
            $include[]['$Alias'] = $this->alias;
        }
        $json->{'$Reference'}[$this->uri.'.json'] = [
            '$Include' => [
                $include
            ]
        ];

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return is_null($this->alias) ? $this->namespace : $this->alias . '=' . $this->namespace;
    }
}