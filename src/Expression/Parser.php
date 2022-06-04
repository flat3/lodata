<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Node\Func;
use Flat3\Lodata\Expression\Node\Group;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Lambda;
use Flat3\Lodata\Expression\Node\Operator\Logical;
use Flat3\Lodata\Expression\Node\Property;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Identifier;
use Illuminate\Support\Arr;

/**
 * Parser
 * @link http://www.reedbeta.com/blog/the-shunting-yard-algorithm/
 * @package Flat3\Lodata\Expression
 */
abstract class Parser
{
    /**
     * The list of symbols supported by this parser
     * @var Node[] $symbols
     */
    protected $symbols = [];

    /**
     * The list of tokens discovered by this parser
     * @var Node[] $tokens
     */
    protected $tokens = [];

    /**
     * The entity set this parser is being run on behalf of
     * @var EntitySet[] $entitySets
     */
    protected $entitySets = [];

    /**
     * The operator stack
     * @var Operator[] $operatorStack
     */
    protected $operatorStack = [];

    /**
     * The operand stack
     * @var Node[] $operandStack
     */
    protected $operandStack = [];

    /**
     * The lexer instance
     * @var Lexer $lexer
     */
    protected $lexer;

    public function __construct()
    {
        $this->symbols = collect($this->symbols)->keyBy(function (string $node) {
            return strtolower($node::symbol);
        })->toArray();
    }

    /**
     * Convert an expression to an abstract syntax tree.
     * @param  string  $expression  The expression, in infix notation.
     * @return Node that serves as the root of the AST.
     */
    public function generateTree(string $expression): Node
    {
        $this->lexer = new Lexer($expression);

        while (!$this->lexer->finished()) {
            if ($this->findToken()) {
                continue;
            }

            throw new ParserException('Encountered an invalid symbol', $this->lexer);
        }

        /**
         * When we get to the end of the formula, apply any operators remaining on the stack, from the top down.
         */
        while ($this->operatorStack) {
            $this->applyOperator(array_pop($this->operatorStack));
        }

        /**
         * Then the result is the only item left on the operand stack (assuming well-formed input).
         */
        return array_pop($this->operandStack);
    }

    /**
     * A function that returns whether a valid token was found
     * @return bool
     */
    abstract protected function findToken(): bool;

    /**
     * Add the provided operator as an AST node
     * @param  Operator  $operator  Operator
     * @throws ParserException
     */
    private function applyOperator(Operator $operator): void
    {
        if ($operator instanceof Func) {
            $this->operandStack[] = $operator;

            return;
        }

        if ($operator instanceof Lambda) {
            $lambdaVariable = array_pop($this->operandStack);
            $navigationProperty = array_pop($this->operandStack);

            if (!$navigationProperty instanceof Node\Property\Navigation) {
                throw new ParserException('Lambda function was not prepended by a navigation property path');
            }

            if (!$lambdaVariable instanceof Literal\LambdaVariable) {
                throw new ParserException('Lambda function had no valid argument');
            }

            $operator->setVariable($lambdaVariable);
            $operator->setNavigationProperty($navigationProperty);

            $this->operandStack[] = $operator;

            return;
        }

        if ($operator::isUnary()) {
            $operand = array_pop($this->operandStack);

            if (!$operand) {
                throw new ParserException('An operator was used without an operand');
            }

            $operator->setLeftNode($operand);
            $this->operandStack[] = $operator;

            return;
        }

        $rightOperand = array_pop($this->operandStack);
        $leftOperand = array_pop($this->operandStack);

        if (!$rightOperand || !$leftOperand) {
            throw new ParserException('An operator was used without an operand', $this->lexer);
        }

        $operator->setRightNode($rightOperand);
        $operator->setLeftNode($leftOperand);
        $this->operandStack[] = $operator;
    }

    /**
     * Tokenize spaces in the expression
     * @return bool
     */
    public function tokenizeSpace(): bool
    {
        return !!$this->lexer->maybeChar(' ');
    }

    /**
     * When you see a left paren, push it on the operator stack; no other operators can pop a paren (so it’s as if it has the lowest precedence).
     */
    public function tokenizeLeftParen(): bool
    {
        if (!$this->lexer->maybeChar('(')) {
            return false;
        }

        $token = new Group($this);
        $this->operatorStack[] = $token;
        $this->tokens[] = $token;

        $lastToken = $this->getLastToken();
        if ($lastToken instanceof Func || $lastToken instanceof Logical\In || $lastToken instanceof Lambda) {
            $token->setFunc($lastToken);
        }

        return true;
    }

    /**
     * Get the token that was discovered before the current token
     * @return Node|null
     */
    public function getLastToken(): ?Node
    {
        return $this->tokens[count($this->tokens) - 2] ?? null;
    }

    /**
     * Then when you see a right paren, pop-and-apply any operators on the stack until you get back to a left paren, which is popped and discarded.
     */
    public function tokenizeRightParen(): bool
    {
        if (!$this->lexer->maybeChar(')')) {
            return false;
        }

        while ($this->operatorStack) {
            $headOperator = $this->getOperatorStackHead();
            if ($headOperator instanceof Group) {
                /** @var Group $paren */
                $paren = array_pop($this->operatorStack);
                $func = $paren->getFunc();
                if ($func && $this->operandStack && ($func instanceof Lambda || $func instanceof Logical\In || ($func instanceof Func && $func::arguments > 0))) {
                    $func->addArgument(array_pop($this->operandStack));
                }

                return true;
            } else {
                /** @var Operator $operator */
                $operator = array_pop($this->operatorStack);
                $this->applyOperator($operator);
            }
        }

        throw (new BadRequestException(
            'unbalanced_right_parentheses',
            'Unbalanced right parentheses'
        ))->lexer($this->lexer);
    }

    /**
     * Return the node at the top of the operator stack
     * @return Node
     */
    public function getOperatorStackHead(): ?Node
    {
        $operator = array_pop($this->operatorStack);
        $this->operatorStack[] = $operator;

        return $operator;
    }

    /**
     * When a comma is encountered, pop-and-apply operators back to a left paren; the operand on the top of the stack is then the next argument,
     * and should be popped and added to the argument list.
     */
    public function tokenizeSeparator(): bool
    {
        if (!$this->lexer->maybeExpression(',\s?')) {
            return false;
        }

        while ($this->operatorStack) {
            $headOperator = $this->getOperatorStackHead();
            if ($headOperator instanceof Group) {
                $arg = array_pop($this->operandStack);
                $headOperator->getFunc()->addArgument($arg);

                return true;
            } else {
                /** @var Operator $operator */
                $operator = array_pop($this->operatorStack);
                $this->applyOperator($operator);
            }
        }

        return true;
    }

    /**
     * If we see an operator
     */
    public function tokenizeOperator(): bool
    {
        $token = null;

        foreach ($this->symbols as $symbol) {
            switch (true) {
                case is_a($symbol, Func::class, true):
                case is_a($symbol, Lambda::class, true):
                    $token = $this->lexer->func($symbol::getSymbol());
                    break;

                case is_a($symbol, Not_::class, true):
                    $token = $this->lexer->unaryOperator($symbol::getSymbol());
                    break;

                default:
                    $token = $this->lexer->operator($symbol::getSymbol());
                    break;
            }

            if ($token) {
                break;
            }
        }

        if (!$token) {
            return false;
        }

        $token = strtolower($token);

        /**
         * While there’s an operator on top of the operator stack of precedence higher than or equal to that of the operator we’re currently processing, pop it off and "apply" it.
         * (That is, pop the required operand(s) off the stack, "apply" the operator to them, and push the result back on the operand stack.)
         *
         * When processing a unary operator, it’s only allowed to pop-and-apply other unary operators—never any binary ones, regardless of precedence.
         */
        /**
         * @var Operator $o1
         */
        $o1 = new $this->symbols[$token]($this);
        $o1->setValue($token);
        $this->tokens[] = $o1;

        while ($this->operatorStack) {
            /** @var Operator $o2 */
            $o2 = $this->getOperatorStackHead();

            if (null === $o2 || $o2 instanceof Group) {
                break;
            }

            if (
                (
                    !$o1::isUnary() ||
                    ($o1::isUnary() && $o2::isUnary())
                ) &&
                $o2::getPrecedence() >= $o1::getPrecedence()
            ) {
                array_pop($this->operatorStack);
                $this->applyOperator($o2);
            } else {
                break;
            }
        }

        /**
         * Then, push the current operator on the operator stack.
         */
        $this->operatorStack[] = $o1;

        return true;
    }

    /**
     * Tokenize a GUID
     * @return bool
     */
    public function tokenizeGuid(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->guid();
        });

        if (!$token) {
            return false;
        }

        $operand = new Literal\Guid($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a duration
     * @return bool
     */
    public function tokenizeDuration(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->duration();
        });

        if (!$token) {
            return false;
        }

        $operand = new Literal\Duration($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize an enum
     * @return bool
     */
    public function tokenizeEnum(): bool
    {
        $resource = $this->getCurrentResource();

        if (!$resource) {
            return false;
        }

        $enum = $this->lexer->with(function () {
            $identifier = $this->lexer->with(function () {
                return $this->lexer->qualifiedIdentifier();
            });

            if (!$identifier) {
                $identifier = $this->lexer->identifier();
            }

            if (!$identifier) {
                return null;
            }

            $type = Lodata::getTypeDefinition((new Identifier($identifier))->getName());

            if (!$type instanceof EnumerationType) {
                return null;
            }

            $flag = $this->lexer->quotedString();

            return $type->instance($flag);
        });

        if (!$enum) {
            return false;
        }

        $operand = new Literal\Enum($this);
        $operand->setValue($enum);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize literal null
     * @return bool
     */
    public function tokenizeNull(): bool
    {
        $token = $this->lexer->maybeLiteral(Constants::null);

        if (null === $token) {
            return false;
        }

        $operand = new Literal\Null_($this);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a number
     * @return bool
     */
    public function tokenizeNumber(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->number();
        });

        if (null === $token) {
            return false;
        }

        $operand = is_int($token) ? new Literal\Int64($this) : new Literal\Double($this);
        $operand->setValue($token);

        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a boolean
     * @return bool
     */
    public function tokenizeBoolean(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->boolean();
        });

        if (!$token) {
            return false;
        }

        $operand = new Literal\Boolean($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a single quoted string
     * @return bool
     */
    public function tokenizeSingleQuotedString(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->quotedString();
        });

        if (null === $token) {
            return false;
        }

        $operand = new Literal\String_($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a double quoted string
     * @return bool
     */
    public function tokenizeDoubleQuotedString(): bool
    {
        $token = $this->lexer->maybeDoubleQuotedString();

        if (null === $token) {
            return false;
        }

        $operand = new Literal\String_($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a date time offset
     * @return bool
     */
    public function tokenizeDateTimeOffset(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->datetimeoffset();
        });

        if (!$token) {
            return false;
        }

        $operand = new Literal\DateTimeOffset($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a date
     * @return bool
     */
    public function tokenizeDate(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->date();
        });

        if (!$token) {
            return false;
        }

        $operand = new Literal\Date($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a time of day
     * @return bool
     */
    public function tokenizeTimeOfDay(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->timeOfDay();
        });

        if (!$token) {
            return false;
        }

        $operand = new Literal\TimeOfDay($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a navigation property path
     * @return bool
     */
    public function tokenizeNavigationPropertyPath(): bool
    {
        $currentResource = $this->getCurrentResource();

        if (!$currentResource) {
            return false;
        }

        $navigationProperties = $currentResource->getType()->getNavigationProperties();

        $token = $this->lexer->with(function () {
            $identifier = $this->lexer->identifier();
            $this->lexer->char(Lexer::pathSeparator);

            return $identifier;
        });

        if (!$token) {
            return false;
        }

        $property = $navigationProperties->get($token);

        if (!$property) {
            return false;
        }

        $operand = new Node\Property\Navigation($this);
        $operand->setValue($property);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize the lambda operator argument
     * @return bool
     */
    public function tokenizeLambdaVariable(): bool
    {
        $token = $this->lexer->with(function () {
            return $this->lexer->expression(Lexer::lambdaVariable);
        });

        if (!$token) {
            return false;
        }

        $lambdaVariable = rtrim($token, ':');

        $operand = new Node\Literal\LambdaVariable($this);
        $operand->setValue($lambdaVariable);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a lambda property
     * @return bool
     */
    public function tokenizeLambdaProperty(): bool
    {
        $variable = null;

        foreach (array_reverse($this->tokens) as $token) {
            if ($token instanceof Literal\LambdaVariable) {
                $variable = $token;
                break;
            }
        }

        if (!$variable) {
            return false;
        }

        $preamble = $this->lexer->maybeLiteral($variable->getValue().'/');

        if (!$preamble) {
            return false;
        }

        $token = $this->lexer->identifier();

        $operand = new Node\Property\Lambda($this);
        $operand->setValue($token);
        $operand->setVariable($variable);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Get the entity set currently being operated on
     * @return EntitySet Entity set
     */
    public function getCurrentResource(): ?EntitySet
    {
        return Arr::last($this->entitySets);
    }

    /**
     * Push an entity set onto the stack
     * @param  EntitySet  $entitySet  Entity set
     * @return $this Parser
     */
    public function pushEntitySet(EntitySet $entitySet): self
    {
        $this->entitySets[] = $entitySet;

        return $this;
    }

    /**
     * Pop an entity set off the stack
     * @return EntitySet Entity set
     */
    public function popEntitySet(): EntitySet
    {
        return array_pop($this->entitySets);
    }

    /**
     * Tokenize a declared property
     * @return bool
     */
    public function tokenizeDeclaredProperty(): bool
    {
        $currentResource = $this->getCurrentResource();

        if (!$currentResource) {
            return false;
        }

        $properties = $currentResource->getType()->getDeclaredProperties();

        $property = $this->lexer->with(function () use ($properties) {
            $token = $this->lexer->identifier();
            return $properties->get($token);
        });

        if (!$property) {
            return false;
        }

        $operand = new Property\Declared($this);
        $operand->setValue($property);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a computed property
     * @return bool
     */
    public function tokenizeComputedProperty(): bool
    {
        $currentResource = $this->getCurrentResource();

        if (!$currentResource) {
            return false;
        }

        if (!$currentResource->getTransaction()) {
            return false;
        }

        $compute = $currentResource->getCompute();

        if (!$compute->hasValue()) {
            return false;
        }

        $properties = $compute->getProperties();

        $property = $this->lexer->with(function () use ($properties) {
            $token = $this->lexer->identifier();
            return $properties->get($token);
        });

        if (!$property) {
            return false;
        }

        $operand = new Property\Computed($this);
        $operand->setValue($property);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a search string, where any literal string but an operator followed by a word boundary is valid
     * @return bool
     */
    public function tokenizeNonOperatorString(): bool
    {
        $exceptions = join('|', array_map(function ($operator) {
            return $operator.'\b';
        }, array_keys($this->symbols)));

        $expression = '(?!'.$exceptions.')([^ \'"\(\)]+)';

        $token = $this->lexer->maybeExpression($expression, false);

        if (!$token) {
            return false;
        }

        $operand = new Literal\String_($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }
}
