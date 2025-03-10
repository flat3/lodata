<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type;

#[Attribute]
class LodataOperation
{
    const operationType = Operation::function;

    protected ?string $name = null;
    protected ?string $bind = null;
    protected ?Type $return = null;

    public function __construct(?string $name = null, ?string $bind = null, ?string $return = null)
    {
        $this->name = $name;
        $this->bind = $bind;
        if ($return) {
            $this->return = Lodata::getComplexType($return);
        }
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function hasBindingParameterName(): bool
    {
        return $this->bind !== null;
    }

    public function getBindingParameterName(): string
    {
        return $this->bind;
    }

    public function hasReturnType(): bool
    {
        return $this->return !== null;
    }

    public function getReturnType(): Type
    {
        return $this->return;
    }
}