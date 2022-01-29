<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression;

use Flat3\Lodata\Exception\Protocol\NotImplementedException;

/**
 * Node
 * @package Flat3\Lodata\Expression
 */
abstract class Node
{
    /**
     * Captured symbol
     * @var string
     */
    public const symbol = '';

    /**
     * Captured value
     * @var mixed $value
     */
    protected $value = null;

    /**
     * Parser that generated this node
     * @var Parser $parser
     */
    protected $parser = null;

    /**
     * List of arguments for this node
     * @var self[]
     */
    private $args = [];

    /**
     * Left-hand argument for this node
     * @var self $arg1
     */
    private $arg1 = null;

    /**
     * Right-hand argument for this node
     * @var self $arg2
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
     * @param  mixed  $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

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
     * Get the first argument for this operator
     * @return Node|null
     */
    public function getArgument(): ?Node
    {
        return $this->args[0] ?? null;
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
     * Get the parser that generated this node
     * @return Parser
     */
    public function getParser(): Parser
    {
        return $this->parser;
    }

    /**
     * Throw an exception if this node cannot be handled
     * @return void
     */
    public function notImplemented(): void
    {
        throw new NotImplementedException(
            'unsupported_expression',
            'This entity set does not support the provided expression'
        );
    }
}
