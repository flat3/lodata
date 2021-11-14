<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Operations;

use Flat3\Lodata\Attributes\LodataAction;
use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\Entity;

class Service
{
    #[LodataFunction]
    public function hello(): string
    {
        return 'hello world!';
    }

    #[LodataFunction]
    public function identity(string $arg): string
    {
        return $arg;
    }

    #[LodataFunction(bind: "Flight")]
    public function name(Entity $Flight): string
    {
        return $Flight->getSource()->name;
    }

    #[LodataFunction(bind: "Flight", return: "Flight")]
    public function i1(Entity $Flight): Entity
    {
        $Flight['name'] = 'test';

        return $Flight;
    }

    #[LodataFunction]
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    #[LodataFunction(bind: "a")]
    public function increment(int $a): int
    {
        return $a + 1;
    }

    #[LodataAction]
    public function exec(): void
    {
    }

    #[LodataAction(name: "exec2")]
    public function exec1(): void
    {
    }
}