<?php

namespace Flat3\OData\Interfaces;

interface ServiceInterface extends NamedInterface
{
    public function getKind(): string;

    public function getTitle(): ?string;

    public function setTitle(string $title);
}