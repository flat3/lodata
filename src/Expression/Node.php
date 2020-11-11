<?php

namespace Flat3\Lodata\Expression;

use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Node\Func;

/**
 * Node
 * @package Flat3\Lodata\Expression
 */
abstract class Node
{
    /**
     * Captured symbol
     * @var string
     * @internal
     */
    public const symbol = '';

    /**
     * Captured value
     * @var mixed $value
     * @internal
     */
    protected $value = null;

    /**
     * Parser that generated this node
     * @var Parser $parser
     * @internal
     */
    protected $parser = null;

    /**
     * List of arguments for this node
     * @var self[]
     * @internal
     */
    private $args = [];

    /**
     * Left-hand argument for this node
     * @var self $arg1
     * @internal
     */
    private $arg1 = null;

    /**
     * Right-hand argument for this node
     * @var self $arg2
     * @internal
     */
    private $arg2 = null;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Set the left node
     * @param  Node  $arg
     */
    public function setLeftNode(Node $arg): void
    {
        $this->arg1 = $arg;
    }

    /**
     * Set the right node
     * @param  Node  $arg
     */
    public function setRightNode(Node $arg): void
    {
        $this->arg2 = $arg;
    }

    /**
     * Add an argument to the argument list
     * @param  Node  $arg
     */
    public function addArgument(Node $arg): void
    {
        $this->args[] = $arg;
    }

    /**
     * Return the value of this node
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this node
     * @param  string  $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * Compute the value of this node
     * @return void
     */
    abstract public function compute(): void;

    /**
     * Get the left node
     * @return self
     */
    public function getLeftNode(): ?Node
    {
        return $this->arg1;
    }

    /**
     * Get the right node
     * @return self
     */
    public function getRightNode(): ?Node
    {
        return $this->arg2;
    }

    /**
     * Convert this node to a string representation
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Get the arguments list for this operator
     * @return Node[]
     */
    public function getArguments(): array
    {
        return $this->args;
    }

    /**
     * Handle an expression event
     * @param  Event  $event  Event
     * @throws NotImplementedException
     */
    protected function expressionEvent(Event $event): void
    {
        if ($this->parser->expressionEvent($event) === true) {
            return;
        }

        $node = $event->getNode();

        switch (true) {
            case $node instanceof Operator:
                throw new NotImplementedException(
                    'unsupported_operator',
                    sprintf(
                        'This entity set does not support the operator "%s"',
                        $node::symbol
                    )
                );

            case $node instanceof Func:
                throw new NotImplementedException(
                    'unsupported_function',
                    sprintf(
                        'This entity set does not support the function "%s"',
                        $node::symbol
                    )
                );

            default:
                throw new NotImplementedException('unsupported_expression',
                    'This entity set does not support the provided expression');
        }
    }
}
