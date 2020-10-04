<?php

namespace Flat3\OData\Interfaces;

interface NamedInterface
{
    public function getName(): string;

    public function getTitle(): ?string;
}