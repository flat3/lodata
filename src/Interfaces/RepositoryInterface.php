<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

interface RepositoryInterface
{
    public function getClass(): string;
}