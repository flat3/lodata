<?php

namespace Flat3\OData\Expression\Parser;

use Flat3\OData\Exception\Internal\ParserException;
use Flat3\OData\Expression\Event;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Expression\Node;
use Flat3\OData\Expression\Operator;
use Flat3\OData\Expression\Parser;
use Flat3\OData\Resource\EntitySet;
use Flat3\OData\Transaction;

class Filter extends Parser
{
    /**
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_BuiltinQueryFunctions
     */
    public const operators = [
        // Primary
        Node\Operator\Logical\In::class,

        Node\Func\StringCollection\Concat::class,
        Node\Func\StringCollection\Contains::class,
        Node\Func\StringCollection\EndsWith::class,
        Node\Func\StringCollection\IndexOf::class,
        Node\Func\StringCollection\Length::class,
        Node\Func\StringCollection\StartsWith::class,
        Node\Func\StringCollection\Substring::class,

        Node\Func\Arithmetic\Ceiling::class,
        Node\Func\Arithmetic\Floor::class,
        Node\Func\Arithmetic\Round::class,

        Node\Func\String\MatchesPattern::class,
        Node\Func\String\ToLower::class,
        Node\Func\String\ToUpper::class,
        Node\Func\String\Trim::class,

        Node\Func\DateTime\Day::class,
        Node\Func\DateTime\Date::class,
        Node\Func\DateTime\Hour::class,
        Node\Func\DateTime\Minute::class,
        Node\Func\DateTime\Month::class,
        Node\Func\DateTime\Now::class,
        Node\Func\DateTime\Second::class,
        Node\Func\DateTime\Time::class,
        Node\Func\DateTime\Year::class,

        // Unary
        Node\Operator\Comparison\Not_::class,

        // Multiplicative
        Node\Operator\Arithmetic\Mul::class,
        Node\Operator\Arithmetic\Div::class,
        Node\Operator\Arithmetic\DivBy::class,
        Node\Operator\Arithmetic\Mod::class,

        // Additive
        Node\Operator\Arithmetic\Add::class,
        Node\Operator\Arithmetic\Sub::class,

        // Relational
        Node\Operator\Logical\GreaterThan::class,
        Node\Operator\Logical\GreaterThanOrEqual::class,
        Node\Operator\Logical\LessThan::class,
        Node\Operator\Logical\LessThanOrEqual::class,

        // Equality
        Node\Operator\Logical\Equal::class,
        Node\Operator\Logical\NotEqual::class,

        // Conditional AND
        Node\Operator\Comparison\And_::class,

        // Conditional OR
        Node\Operator\Comparison\Or_::class,
    ];

    /** @var Transaction $transaction */
    protected $transaction;

    public function __construct(EntitySet $store, Transaction $transaction)
    {
        parent::__construct($store);

        $this->transaction = $transaction;

        /** @var Operator $operator */
        foreach (self::operators as $operator) {
            $this->operators[$operator::getSymbol()] = $operator;
        }
    }

    public function expressionEvent(Event $event): ?bool
    {
        return $this->store->filter($event);
    }

    public function findLiteral(): bool
    {
        return $this->tokenizeDateTimeOffset() ||
            $this->tokenizeDate() ||
            $this->tokenizeTimeOfDay() ||
            $this->tokenizeSingleQuotedString() ||
            $this->tokenizeGuid() ||
            $this->tokenizeNumber() ||
            $this->tokenizeBoolean();
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
            $this->tokenizeNull() ||
            $this->tokenizeLeftParen() ||
            $this->tokenizeRightParen() ||
            $this->tokenizeComma() ||
            $this->tokenizeParameterAlias($this->transaction) ||
            $this->tokenizeKeyword() ||
            $this->tokenizeOperator() ||
            $this->findLiteral();
    }

    public function tokenizeParameterAlias(Transaction $transaction): bool
    {
        $token = $this->lexer->maybeParameterAlias();

        if (!$token) {
            return false;
        }

        $referencedValue = $transaction->getReferencedValue($token);
        $lexer = $this->lexer;
        $this->lexer = new Lexer($referencedValue);

        while (!$this->lexer->finished()) {
            if ($this->findLiteral()) {
                continue;
            }

            throw new ParserException('Encountered an invalid symbol', $this->lexer);
        }

        $this->lexer = $lexer;

        return true;
    }
}
