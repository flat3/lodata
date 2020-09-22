<?php

namespace Flat3\OData\Expression;

use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Expression\Node\Func;

abstract class Node
{
    /** @var string */
    public const symbol = '';

    /** @var mixed $value */
    protected $value = null;

    /** @var Parser $parser */
    protected $parser = null;

    /** @var self[] */
    private $args = [];

    /** @var self $arg1 */
    private $arg1 = null;

    /** @var self $arg2 */
    private $arg2 = null;

    /** @var self $arg3 */
    private $arg3 = null;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Set the left node
     *
     * @param  Node  $arg
     */
    public function setLeftNode(Node $arg): void
    {
        $this->arg1 = $arg;
    }

    /**
     * Set the right node
     *
     * @param  Node  $arg
     */
    public function setRightNode(Node $arg): void
    {
        $this->arg2 = $arg;
    }

    /**
     * Add an argument to the argument list
     *
     * @param  Node  $arg
     */
    public function addArgument(Node $arg): void
    {
        $this->args[] = $arg;
    }

    /**
     * Return the value of this node
     *
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this node
     *
     * @param  mixed  $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * Compute the value of this node
     *
     * @return void
     */
    abstract public function compute(): void;

    /**
     * @return self The left node, or <code>null</code> if there isn't one
     */
    public function getLeftNode(): ?Node
    {
        return $this->arg1;
    }

    /**
     * @return self The right node, or <code>null</code> if there isn't one
     */
    public function getRightNode(): ?Node
    {
        return $this->arg2;
    }

    public function __toString()
    {
        return $this->value;
    }

    /**
     * Get the arguments list for this operator
     *
     * @return Node[]
     */
    public function getArguments(): array
    {
        return $this->args;
    }

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
                        'This query does not support the operator "%s"',
                        $node::symbol
                    )
                );

            case $node instanceof Func:
                throw new NotImplementedException(
                    'unsupported_function',
                    sprintf(
                        'This query does not support the function "%s"',
                        $node::symbol
                    )
                );

            default:
                throw new NotImplementedException('unsupported_expression',
                    'This query does not support the provided expression');
        }
    }
}
