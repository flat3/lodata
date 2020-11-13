<?php

namespace Flat3\Lodata\Expression\Parser;

use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Operator;
use Flat3\Lodata\Expression\Parser;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;

/**
 * Search
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_BuiltinQueryFunctions
 * @package Flat3\Lodata\Expression\Parser
 */
class Search extends Parser
{
    public const operators = [
        Node\Operator\Comparison\Not_::class,
        Node\Operator\Comparison\And_::class,
        Node\Operator\Comparison\Or_::class,
    ];

    public function __construct()
    {
        parent::__construct();

        /** @var Operator $operator */
        foreach (self::operators as $operator) {
            $this->operators[strtoupper($operator::getSymbol())] = $operator;
        }
    }

    /**
     * Handle an expression event
     * @param  Event  $event  Event
     * @return bool|null
     */
    public function expressionEvent(Event $event): ?bool
    {
        $entitySet = $this->getCurrentResource();

        if ($entitySet instanceof SearchInterface) {
            return $entitySet->search($event);
        }

        return false;
    }

    /**
     * Valid token types for this expression
     * @return bool
     * @throws ParserException
     */
    protected function findToken(): bool
    {
        return $this->tokenizeSpace() ||
            $this->tokenizeLeftParen() ||
            $this->tokenizeRightParen() ||
            $this->tokenizeOperator() ||
            $this->tokenizeDoubleQuotedString() ||
            $this->tokenizeString();
    }
}
