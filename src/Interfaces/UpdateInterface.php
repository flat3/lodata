<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Entity;

interface UpdateInterface
{
    public function update(): Entity;
}