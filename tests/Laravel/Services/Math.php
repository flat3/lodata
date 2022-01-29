<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Laravel\Services;

use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\Attributes\LodataNamespace;

#[LodataNamespace(name: "com.example.math")]
class Math
{
    #[LodataFunction]
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}