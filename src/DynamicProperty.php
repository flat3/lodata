<?php

namespace Flat3\Lodata;

abstract class DynamicProperty extends Property
{
    abstract public function invoke(Entity $entity);
}
