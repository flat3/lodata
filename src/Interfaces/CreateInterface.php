<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Entity;

interface CreateInterface
{
    public function create(): Entity;
}