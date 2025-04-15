<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation;

use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Traits\HasComplexType;

/**
 * Class Record
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530444
 * @package Flat3\Lodata\Annotation
 */
class Record extends ObjectArray implements TypeInterface
{
    use HasComplexType;

    /**
     * Resource identifier
     * @var Identifier $identifier
     */
    protected $identifier;

    /**
     * Get the identifier
     * @return Identifier Identifier
     */
    public function getIdentifier(): ?Identifier
    {
        return $this->identifier;
    }

    /**
     * Set the identifier
     * @param  string|Identifier  $identifier  Identifier
     * @return $this
     */
    public function setIdentifier($identifier): Record
    {
        $this->identifier = $identifier instanceof Identifier ? $identifier : new Identifier($identifier);

        return $this;
    }
}