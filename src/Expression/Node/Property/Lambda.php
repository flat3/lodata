<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Property;

use Flat3\Lodata\Expression\Node\Literal\LambdaVariable;
use Flat3\Lodata\Expression\Node\Property;

/**
 * Lambda property
 * @package Flat3\Lodata\Expression\Node\Property
 */
class Lambda extends Property
{
    protected $variable;

    public function setVariable(LambdaVariable $variable): self
    {
        $this->variable = $variable;

        return $this;
    }

    public function getVariable(): LambdaVariable
    {
        return $this->variable;
    }
}
