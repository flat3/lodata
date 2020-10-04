<?php

namespace Flat3\OData\Interfaces;

interface ResourceInterface
{
    public function getKind(): string;

    public function getTitle(): ?string;

    public function setTitle(string $title);
}