<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator;

use Flat3\Lodata\Expression\Node\Literal\LambdaVariable;
use Flat3\Lodata\Expression\Node\Property\Navigation;
use Flat3\Lodata\Expression\Operator;

/**
 * Lambda
 * @package Flat3\Lodata\Expression\Node\Operator
 */
abstract class Lambda extends Operator
{
    const unary = true;
    public const precedence = 8;

    /**
     * @var Navigation $navigationProperty
     */
    protected $navigationProperty;

    /**
     * @var LambdaVariable $variable
     */
    protected $variable;

    /**
     * Get the navigation property
     * @return Navigation
     */
    public function getNavigationProperty(): Navigation
    {
        return $this->navigationProperty;
    }

    /**
     * Set the navigation property
     * @param  Navigation  $property
     * @return $this
     */
    public function setNavigationProperty(Navigation $property): self
    {
        $this->navigationProperty = $property;

        return $this;
    }

    /**
     * Get the lambda variable
     * @return LambdaVariable Lambda variable
     */
    public function getVariable(): LambdaVariable
    {
        return $this->variable;
    }

    /**
     * Set the lambda variable
     * @param  LambdaVariable  $variable  Lambda variable
     * @return $this
     */
    public function setVariable(LambdaVariable $variable): self
    {
        $this->variable = $variable;

        return $this;
    }
}
