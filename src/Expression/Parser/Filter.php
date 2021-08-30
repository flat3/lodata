<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Parser;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Operator;
use Flat3\Lodata\Expression\Parser;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;

/**
 * Filter
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_BuiltinQueryFunctions
 * @package Flat3\Lodata\Expression\Parser
 */
class Filter extends Parser
{
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
        Node\Func\DateTime\FractionalSeconds::class,
        Node\Func\DateTime\Date::class,
        Node\Func\DateTime\Hour::class,
        Node\Func\DateTime\MaxDateTime::class,
        Node\Func\DateTime\MinDateTime::class,
        Node\Func\DateTime\Minute::class,
        Node\Func\DateTime\Month::class,
        Node\Func\DateTime\Now::class,
        Node\Func\DateTime\Second::class,
        Node\Func\DateTime\Time::class,
        Node\Func\DateTime\TotalOffsetMinutes::class,
        Node\Func\DateTime\TotalSeconds::class,
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

        // Lambda
        Node\Operator\Lambda\Any::class,
        Node\Operator\Lambda\All::class,
    ];

    /**
     * @var Transaction $transaction
     */
    protected $transaction;

    public function __construct(Transaction $transaction)
    {
        parent::__construct();

        $this->transaction = $transaction;

        /** @var Operator $operator */
        foreach (self::operators as $operator) {
            $this->operators[$operator::getSymbol()] = $operator;
        }
    }

    /**
     * Handle an expression node
     * @param  Node  $node  Node
     * @return bool|null
     */
    public function emit(Node $node): ?bool
    {
        $entitySet = $this->getCurrentResource();

        if ($entitySet instanceof FilterInterface) {
            return $entitySet->filter($node);
        }

        return false;
    }

    /**
     * Tokenize a literal
     * @link https://github.com/oasis-tcs/odata-abnf/blob/master/abnf/odata-abnf-construction-rules.txt#L871
     * @return bool
     */
    public function tokenizeLiteral(): bool
    {
        return $this->tokenizeNull() ||
            $this->tokenizeBoolean() ||
            $this->tokenizeGuid() ||
            $this->tokenizeDateTimeOffset() ||
            $this->tokenizeDate() ||
            $this->tokenizeTimeOfDay() ||
            $this->tokenizeNumber() ||
            $this->tokenizeSingleQuotedString() ||
            $this->tokenizeDuration();
    }

    /**
     * Valid token types for this expression
     * @return bool
     * @throws ParserException
     */
    protected function findToken(): bool
    {
        return $this->tokenizeSpace() ||
            $this->tokenizeLiteral() ||
            $this->tokenizeLeftParen() ||
            $this->tokenizeRightParen() ||
            $this->tokenizeComma() ||
            $this->tokenizeParameterAlias() ||
            $this->tokenizeLambdaVariable() ||
            $this->tokenizeLambdaProperty() ||
            $this->tokenizeDeclaredProperty() ||
            $this->tokenizeOperator() ||
            $this->tokenizeNavigationPropertyPath();
    }

    /**
     * Tokenize a parameter alias
     * @return bool
     */
    public function tokenizeParameterAlias(): bool
    {
        $token = $this->lexer->maybeParameterAlias();

        $transaction = $this->transaction;

        if (!$token) {
            return false;
        }

        $referencedValue = $transaction->getParameterAlias($token);
        $lexer = $this->lexer;
        $this->lexer = new Lexer($referencedValue);

        while (!$this->lexer->finished()) {
            if ($this->tokenizeLiteral()) {
                continue;
            }

            throw new ParserException('Encountered an invalid symbol', $this->lexer);
        }

        $this->lexer = $lexer;

        return true;
    }
}
