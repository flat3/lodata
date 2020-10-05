<?php

namespace Flat3\OData\Type;

/**
 * Class Type
 * @package Flat3\OData
 */
abstract class Base
{
    protected $name = 'Edm.None';

    public function getName(): string
    {
        return $this->name;
    }
}
