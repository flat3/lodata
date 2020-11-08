<?php

namespace Flat3\Lodata\Expression;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Node\Field;
use Flat3\Lodata\Expression\Node\Func;
use Flat3\Lodata\Expression\Node\Group;
use Flat3\Lodata\Expression\Node\LeftParen;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Operator\Logical;
use Flat3\Lodata\Expression\Node\RightParen;

/**
 * Parser
 * @link http://www.reedbeta.com/blog/the-shunting-yard-algorithm/
 * @package Flat3\Lodata\Expression
 */
abstract class Parser
{
    /**
     * The list of operators understood by this parser
     * @var Operator[] $operators
     * @internal
     */
    protected $operators = [];

    /**
     * The list of tokens discovered by this parser
     * @var Node[] $tokens
     * @internal
     */
    protected $tokens = [];

    /**
     * The entity set this parser is being run on behalf of
     * @var EntitySet $entitySet
     * @internal
     */
    protected $entitySet;

    /**
     * The list of valid literal strings that can be captured by this parser
     * @var string[] $validLiterals
     * @internal
     */
    private $validLiterals = [];

    /**
     * The operator stack
     * @var Operator[] $operatorStack
     * @internal
     */
    private $operatorStack = [];

    /**
     * The operand stack
     * @var Node[] $operandStack
     * @internal
     */
    private $operandStack = [];

    /**
     * The lexer instance
     * @var Lexer $lexer
     * @internal
     */
    protected $lexer;

    public function __construct(EntitySet $entitySet)
    {
        $this->entitySet = $entitySet;
    }

    /**
     * Set the list of valid field literals
     * @param  string  $literal
     * @return self
     */
    public function addValidLiteral(string $literal): self
    {
        $this->validLiterals[] = $literal;

        return $this;
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
        if ($operator instanceof LeftParen || $operator instanceof RightParen) {
            throw new ParserException('Expression has unbalanced parentheses');
        }

        if ($operator instanceof Func) {
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
            throw new BadRequestException('missing_operand', 'An operator was used without an operand');
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

        $token = new LeftParen($this);
        $this->operatorStack[] = $token;
        $this->tokens[] = $token;

        $lastToken = $this->getLastToken();
        if ($lastToken instanceof Func || $lastToken instanceof Logical\In) {
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

        $this->tokens[] = new RightParen($this);

        while ($this->operatorStack) {
            $headOperator = $this->getOperatorStackHead();
            if ($headOperator instanceof LeftParen) {
                /** @var LeftParen $paren */
                $paren = array_pop($this->operatorStack);
                if ($headOperator->getFunc() && $this->operandStack) {
                    $paren->getFunc()->addArgument(array_pop($this->operandStack));
                }

                return true;
            } else {
                /** @var Operator $operator */
                $operator = array_pop($this->operatorStack);
                $this->applyOperator($operator);
            }
        }

        throw BadRequestException::factory(
            'unbalanced_right_parentheses',
            'Unbalanced right parentheses'
        )->lexer($this->lexer);
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
    public function tokenizeComma(): bool
    {
        if (!$this->lexer->maybeChar(',')) {
            return false;
        }

        while ($this->operatorStack) {
            $headOperator = $this->getOperatorStackHead();
            if ($headOperator instanceof LeftParen) {
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
        $token = $this->lexer->maybeKeyword(...array_keys($this->operators));

        if (!$token) {
            return false;
        }

        /**
         * While there’s an operator on top of the operator stack of precedence higher than or equal to that of the operator we’re currently processing, pop it off and "apply" it.
         * (That is, pop the required operand(s) off the stack, "apply" the operator to them, and push the result back on the operand stack.)
         *
         * When processing a unary operator, it’s only allowed to pop-and-apply other unary operators—never any binary ones, regardless of precedence.
         */
        /** @var Operator $o1 */
        $o1 = new $this->operators[$token]($this);
        $o1->setValue($token);
        $this->tokens[] = $o1;

        /** @var Operator $o2 */
        $o2 = null;

        while ($this->operatorStack) {
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
        $token = $this->lexer->maybeGuid();
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
        $token = $this->lexer->maybeDuration();
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
     * Tokenize literal null
     * @return bool
     */
    public function tokenizeNull(): bool
    {
        $token = $this->lexer->maybeKeyword('null');
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
        $token = $this->lexer->maybeNumber();

        if (null === $token) {
            return false;
        }

        $operand = new Literal\Double($this);
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
        $token = $this->lexer->maybeBoolean();

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
        $token = $this->lexer->maybeSingleQuotedString();

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
        $token = $this->lexer->maybeDateTimeOffset();

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
        $token = $this->lexer->maybeDate();

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
        $token = $this->lexer->maybeTimeOfDay();

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
     * Tokenize a registered literal keyword
     * @return bool
     */
    public function tokenizeKeyword(): bool
    {
        $token = $this->lexer->maybeKeyword(...$this->validLiterals);

        if (!$token) {
            return false;
        }

        $operand = new Field($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Tokenize a string
     * @return bool
     */
    public function tokenizeString(): bool
    {
        $token = $this->lexer->maybeString();

        if (!$token) {
            return false;
        }

        $operand = new Literal\String_($this);
        $operand->setValue($token);
        $this->operandStack[] = $operand;
        $this->tokens[] = $operand;

        return true;
    }

    /**
     * Dispatch an expression event
     * @param  Event  $event  Event
     * @return bool|null
     */
    abstract public function expressionEvent(Event $event): ?bool;
}
