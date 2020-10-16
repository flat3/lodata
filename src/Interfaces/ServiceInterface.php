<?php

namespace Flat3\Lodata\Interfaces;

interface ServiceInterface
{
    public function getKind(): string;

    public function getIdentifier(): string;

    public function getTitle(): ?string;

    public function setTitle(string $title);
}