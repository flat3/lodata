<?php

namespace Flat3\Lodata\Type;

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
