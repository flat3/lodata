<?php

namespace Flat3\OData\Expression\Parser;

use Flat3\OData\Exception\Internal\ParserException;
use Flat3\OData\Expression\Event;
use Flat3\OData\Expression\Node;
use Flat3\OData\Expression\Operator;
use Flat3\OData\Expression\Parser;
use Flat3\OData\Resource\EntitySet;

class Search extends Parser
{
    /**
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_BuiltinQueryFunctions
     */
    public const operators = [
        Node\Operator\Comparison\Not_::class,
        Node\Operator\Comparison\And_::class,
        Node\Operator\Comparison\Or_::class,
    ];

    public function __construct(EntitySet $store)
    {
        parent::__construct($store);

        /** @var Operator $operator */
        foreach (self::operators as $operator) {
            $this->operators[strtoupper($operator::getSymbol())] = $operator;
        }
    }

    public function expressionEvent(Event $event): ?bool
    {
        return $this->store->search($event);
    }

    /**
     * Valid token types for this expression
     *
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
