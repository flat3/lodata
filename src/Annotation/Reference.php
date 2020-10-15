<?php

namespace Flat3\Lodata\Annotation;

use SimpleXMLElement;

class Reference
{
    protected $uri;
    protected $namespace;

    public function append(SimpleXMLElement $schema): self
    {
        $reference = $schema->addChild('Reference');
        $reference->addAttribute('Uri', $this->uri);
        $include = $reference->addChild('Include');
        $include->addAttribute('Namespace', $this->namespace);

        return $this;
    }

    public function __toString()
    {
        return $this->namespace;
    }
}