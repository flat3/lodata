<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression;

use Carbon\Carbon;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Node\Func;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Operator;
use Flat3\Lodata\Expression\Node\Property;
use Flat3\Lodata\Expression\Operator as OperatorExpression;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type;
use Illuminate\Support\Str;

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
     * Get the parser that generated this node
     * @return Parser
     */
    public function getParser(): Parser
    {
        return $this->parser;
    }

    /**
     * Emit a node
     * @param  Node  $node  Node
     * @throws NotImplementedException
     */
    protected function emit(Node $node): void
    {
        if ($this->parser->emit($node) === true) {
            return;
        }

        switch (true) {
            case $node instanceof OperatorExpression:
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
                throw new NotImplementedException(
                    'unsupported_expression',
                    'This entity set does not support the provided expression'
                );
        }
    }

    /**
     * Evaluate a search expression
     * @param  Entity|null  $entity
     * @return Primitive|null
     */
    public function evaluateSearchExpression(?Entity $entity = null): ?Primitive
    {
        $left = !$this->getLeftNode() ?: $this->getLeftNode()->evaluateSearchExpression($entity);
        $right = !$this->getRightNode() ?: $this->getRightNode()->evaluateSearchExpression($entity);

        $lValue = $left instanceof Primitive ? $left->get() : $left;
        $rValue = $right instanceof Primitive ? $right->get() : $right;

        switch (true) {
            case $this instanceof Literal\String_:
                /** @var DeclaredProperty[] $props */
                $props = $entity->getType()->getDeclaredProperties()->filter(function ($property) {
                    return $property->isSearchable();
                });

                foreach ($props as $prop) {
                    $value = $entity[$prop->getName()];
                    if ($value instanceof PropertyValue) {
                        if (Str::contains($value->getPrimitiveValue(), $this->getValue()->get())) {
                            return Type\Boolean::true();
                        }
                    }
                }

                return Type\Boolean::false();

            case $this instanceof Operator\Comparison\And_:
                return Type\Boolean::factory($lValue && $rValue);

            case $this instanceof Operator\Comparison\Or_:
                return Type\Boolean::factory($lValue || $rValue);

            case $this instanceof Operator\Comparison\Not_:
                return Type\Boolean::factory(!$lValue);
        }

        throw new NotImplementedException();
    }

    /**
     * Evaluate this node using internal logic
     * @param  Entity|null  $entity
     * @return Primitive|null
     */
    public function evaluateCommonExpression(?Entity $entity = null): ?Primitive
    {
        $left = !$this->getLeftNode() ?: $this->getLeftNode()->evaluateCommonExpression($entity);
        $right = !$this->getRightNode() ?: $this->getRightNode()->evaluateCommonExpression($entity);

        $lValue = $left instanceof Primitive ? $left->get() : $left;
        $rValue = $right instanceof Primitive ? $right->get() : $right;

        $args = array_map(function (Node $arg) use ($entity) {
            return $arg->evaluateCommonExpression($entity);
        }, $this->getArguments());

        $argv = array_map(function (?Primitive $arg = null) {
            return $arg ? $arg->get() : null;
        }, $args);

        $arg0 = $args[0] ?? null;
        if ($arg0) {
            $arg0 = $arg0->get();
        }

        switch (true) {
            case $this instanceof Operator\Logical\GreaterThan:
            case $this instanceof Operator\Logical\GreaterThanOrEqual:
            case $this instanceof Operator\Logical\LessThan:
            case $this instanceof Operator\Logical\LessThanOrEqual:
                if ($lValue === null || $rValue === null) {
                    return Type\Boolean::false();
                }
                break;

            case $this instanceof Operator\Arithmetic\Add:
            case $this instanceof Operator\Arithmetic\Sub:
            case $this instanceof Operator\Arithmetic\Mul:
            case $this instanceof Operator\Arithmetic\Div:
            case $this instanceof Operator\Arithmetic\DivBy:
            case $this instanceof Operator\Arithmetic\Mod:
                if ($lValue === null || $rValue === null) {
                    return null;
                }
                break;

            case $this instanceof Operator\Comparison\Not_:
                if ($lValue === null) {
                    return null;
                }
                break;
        }

        switch (true) {
            // Deserialization
            case $this instanceof Property:
                $propertyValue = $entity[$this->getValue()];
                return $propertyValue === null ? null : $propertyValue->getPrimitive();

            case $this instanceof Literal:
                return $this->getValue();

            // 5.1.1.1 Logical operators
            case $this instanceof Operator\Logical\Equal:
                if (array_intersect([$lValue, $rValue], [null, INF, -INF])) {
                    return Type\Boolean::factory($lValue === $rValue);
                }

                if ($this->ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return Type\Boolean::factory($lValue->equalTo($rValue));
                }

                return Type\Boolean::factory($lValue == $rValue);

            case $this instanceof Operator\Logical\NotEqual:
                if (array_intersect([$lValue, $rValue], [null, INF, -INF])) {
                    return Type\Boolean::factory($lValue !== $rValue);
                }

                if ($this->ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return Type\Boolean::factory($lValue->notEqualTo($rValue));
                }

                return Type\Boolean::factory($lValue != $rValue);

            case $this instanceof Operator\Logical\GreaterThan:
                if ($this->ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return Type\Boolean::factory($lValue->greaterThan($rValue));
                }

                return Type\Boolean::factory($lValue > $rValue);

            case $this instanceof Operator\Logical\GreaterThanOrEqual:
                if ($this->ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return Type\Boolean::factory($lValue->greaterThanOrEqualTo($rValue));
                }

                return Type\Boolean::factory($lValue >= $rValue);

            case $this instanceof Operator\Logical\LessThan:
                if ($this->ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return Type\Boolean::factory($lValue->lessThan($rValue));
                }

                return Type\Boolean::factory($lValue < $rValue);

            case $this instanceof Operator\Logical\LessThanOrEqual:
                if ($this->ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return Type\Boolean::factory($lValue->lessThanOrEqualTo($rValue));
                }

                return Type\Boolean::factory($lValue <= $rValue);

            case $this instanceof Operator\Comparison\And_:
                if (($lValue === null && $rValue === false) || ($lValue === false && $rValue === null)) {
                    return Type\Boolean::false();
                }

                if ($lValue === null || $rValue === null) {
                    return null;
                }

                return Type\Boolean::factory($lValue && $rValue);

            case $this instanceof Operator\Comparison\Or_:
                if (($lValue === null && $rValue === true) || ($lValue === true && $rValue === null)) {
                    return Type\Boolean::true();
                }

                if ($lValue === null || $rValue === null) {
                    return null;
                }

                return Type\Boolean::factory($lValue || $rValue);

            case $this instanceof Operator\Comparison\Not_:
                return Type\Boolean::factory(!$lValue);

            case $this instanceof Operator\Logical\In:
                return Type\Boolean::factory(in_array($lValue, $argv));

            // 5.1.1.2 Arithmetic operators
            case $this instanceof Operator\Arithmetic\Add:
                switch (true) {
                    case $left instanceof Type\Duration && $right instanceof Type\Duration:
                        return Type\Duration::factory($lValue + $rValue);

                    case $left instanceof Type\Date && $right instanceof Type\Duration:
                        return Type\Date::factory($lValue->addSeconds($rValue));

                    case $left instanceof Type\Duration && $right instanceof Type\Date:
                        return Type\Date::factory($rValue->addSeconds($lValue));

                    case $left instanceof Type\Duration && $right instanceof Type\DateTimeOffset:
                        return Type\DateTimeOffset::factory($rValue->addSeconds($lValue));

                    case $left instanceof Type\DateTimeOffset && $right instanceof Type\Duration:
                        return Type\DateTimeOffset::factory($lValue->addSeconds($rValue));
                }

                $this->assertTypes([$left, $right], [Type\Numeric::class, Type\Numeric::class]);

                $primitive = new Type\Int64();

                switch (true) {
                    case $left instanceof Type\Duration || $right instanceof Type\Duration:
                        $primitive = new Type\Duration();
                        break;

                    case $left instanceof Type\Decimal || $right instanceof Type\Decimal:
                        $primitive = new Type\Double();
                        break;
                }

                return $primitive->set($lValue + $rValue);

            case $this instanceof Operator\Arithmetic\Sub:
                switch (true) {
                    case $left instanceof Type\Duration && $right instanceof Type\Duration:
                        return Type\Duration::factory($lValue - $rValue);

                    case $left instanceof Type\Date && $right instanceof Type\Duration:
                        return Type\Date::factory($lValue->subSeconds($rValue));

                    case $left instanceof Type\Duration && $right instanceof Type\Date:
                        return Type\Date::factory($rValue->subSeconds($lValue));

                    case $left instanceof Type\Duration && $right instanceof Type\DateTimeOffset:
                        return Type\DateTimeOffset::factory($rValue->subSeconds($lValue));

                    case $left instanceof Type\DateTimeOffset && $right instanceof Type\Duration:
                        return Type\DateTimeOffset::factory($lValue->subSeconds($rValue));
                }

                $this->assertTypes([$left, $right], [Type\Numeric::class, Type\Numeric::class]);

                $primitive = new Type\Int64();

                switch (true) {
                    case $left instanceof Type\Duration || $right instanceof Type\Duration:
                        $primitive = new Type\Duration();
                        break;

                    case $left instanceof Type\Decimal || $right instanceof Type\Decimal:
                        $primitive = new Type\Double();
                        break;
                }

                return $primitive->set($lValue - $rValue);

            case $this instanceof Operator\Arithmetic\Mul:
                $this->assertTypes(
                    [$left, $right],
                    [Type\Numeric::class, Type\Numeric::class],
                    [Type\Numeric::class, Type\Duration::class],
                    [Type\Duration::class, Type\Numeric::class]
                );

                $primitive = new Type\Int64();

                switch (true) {
                    case $left instanceof Type\Duration || $right instanceof Type\Duration:
                        $primitive = new Type\Duration();
                        break;

                    case $left instanceof Type\Decimal || $right instanceof Type\Decimal:
                        $primitive = new Type\Double();
                        break;
                }

                return $primitive->set($lValue * $rValue);

            case $this instanceof Operator\Arithmetic\Div:
                $this->assertTypes(
                    [$left, $right],
                    [Type\Numeric::class, Type\Numeric::class],
                    [Type\Numeric::class, Type\Duration::class],
                    [Type\Duration::class, Type\Numeric::class]
                );

                if ($rValue == 0) {
                    $this->assertTypes([$left], [Type\Decimal::class]);

                    switch (true) {
                        case $lValue > 0:
                            return Type\Decimal::factory(INF);

                        case $lValue < 0:
                            return Type\Decimal::factory(-INF);

                        case $lValue == 0:
                            return Type\Decimal::factory(NAN);
                    }
                }

                $primitive = new Type\Decimal();

                switch (true) {
                    case $left instanceof Type\Duration || $right instanceof Type\Duration:
                        $primitive = new Type\Duration();
                        break;
                }

                if ($left instanceof Type\Byte && $right instanceof Type\Byte) {
                    return Type\Int64::factory($lValue / $rValue);
                }

                return $primitive->set($lValue / $rValue);

            case $this instanceof Operator\Arithmetic\DivBy:
                $this->assertTypes(
                    [$left, $right],
                    [Type\Numeric::class, Type\Numeric::class],
                    [Type\Numeric::class, Type\Duration::class],
                    [Type\Duration::class, Type\Numeric::class],
                );

                if ($rValue == 0) {
                    $this->assertTypes([$left], [Type\Decimal::class]);

                    switch (true) {
                        case $lValue > 0:
                            return Type\Decimal::factory(INF);

                        case $lValue < 0:
                            return Type\Decimal::factory(-INF);

                        case $lValue == 0:
                            return Type\Decimal::factory(NAN);
                    }
                }

                $primitive = new Type\Decimal();

                switch (true) {
                    case $left instanceof Type\Duration || $right instanceof Type\Duration:
                        $primitive = new Type\Duration();
                        break;
                }

                return $primitive->set((float) $lValue / (float) $rValue);

            case $this instanceof Operator\Arithmetic\Mod:
                $this->assertTypes([$left, $right], [Type\Numeric::class, Type\Numeric::class]);

                if ($rValue == 0) {
                    throw new BadRequestException('division_by_zero', 'A division by zero was encountered');
                }

                return Type\Double::factory($lValue % $rValue);

            // 5.1.1.5 String and Collection Functions
            case $this instanceof Node\Func\StringCollection\Concat:
                $this->assertTypes($args, [Type\String_::class, Type\String_::class]);
                return Type\String_::factory(join('', $argv));

            case $this instanceof Node\Func\StringCollection\Contains:
                $this->assertTypes($args, [Type\String_::class, Type\String_::class]);
                return Type\Boolean::factory(Str::contains(...$argv));

            case $this instanceof Node\Func\StringCollection\EndsWith:
                $this->assertTypes($args, [Type\String_::class, Type\String_::class]);
                return Type\Boolean::factory(Str::endsWith(...$argv));

            case $this instanceof Node\Func\StringCollection\IndexOf:
                $this->assertTypes($args, [Type\String_::class, Type\String_::class]);
                $position = strpos(...$argv);
                return $position === false ? Type\Int32::factory(-1) : Type\Int32::factory($position);

            case $this instanceof Node\Func\StringCollection\Length:
                $this->assertTypes($args, [Type\String_::class]);
                return Type\Int32::factory(Str::length(...$argv));

            case $this instanceof Node\Func\StringCollection\StartsWith:
                $this->assertTypes($args, [Type\String_::class, Type\String_::class]);
                return Type\Boolean::factory(Str::startsWith(...$argv));

            case $this instanceof Node\Func\StringCollection\Substring:
                $this->assertTypes(
                    $args,
                    [Type\String_::class, Type\Byte::class],
                    [Type\String_::class, Type\Byte::class, Type\Byte::class]
                );
                return Type\String_::factory(substr(...$argv));

            // 5.1.1.7 String functions
            case $this instanceof Node\Func\String\MatchesPattern:
                $this->assertTypes($args, [Type\String_::class, Type\String_::class]);
                return Type\Boolean::factory(1 === preg_match('/'.$argv[1].'/', $argv[0]));

            case $this instanceof Node\Func\String\ToLower:
                $this->assertTypes($args, [Type\String_::class]);
                return Type\String_::factory(strtolower(...$argv));

            case $this instanceof Node\Func\String\ToUpper:
                $this->assertTypes($args, [Type\String_::class]);
                return Type\String_::factory(strtoupper(...$argv));

            case $this instanceof Node\Func\String\Trim:
                $this->assertTypes($args, [Type\String_::class]);
                return Type\String_::factory(trim(...$argv));

            // 5.1.1.8 Date and time functions
            case $this instanceof Node\Func\DateTime\Date:
                $this->assertTypes($args, [Type\DateTimeOffset::class]);
                return Type\Date::factory($arg0 ? $arg0->format(Type\Date::dateFormat) : null);

            case $this instanceof Node\Func\DateTime\Day:
                $this->assertTypes($args, [Type\Date::class], [Type\DateTimeOffset::class]);
                return Type\Int32::factory($arg0 ? $arg0->day : null);

            case $this instanceof Node\Func\DateTime\FractionalSeconds:
                $this->assertTypes($args, [Type\TimeOfDay::class], [Type\DateTimeOffset::class]);
                return Type\Decimal::factory($arg0 ? $arg0->micro / 1000000 : null);

            case $this instanceof Node\Func\DateTime\Hour:
                $this->assertTypes($args, [Type\TimeOfDay::class], [Type\DateTimeOffset::class]);
                return Type\Int32::factory($arg0 ? $arg0->hour : null);

            case $this instanceof Node\Func\DateTime\MaxDateTime:
                return Type\DateTimeOffset::factory(Carbon::maxValue());

            case $this instanceof Node\Func\DateTime\MinDateTime:
                return Type\DateTimeOffset::factory(Carbon::minValue());

            case $this instanceof Node\Func\DateTime\Minute:
                $this->assertTypes($args, [Type\TimeOfDay::class], [Type\DateTimeOffset::class]);
                return Type\Int32::factory($arg0 ? $arg0->minute : null);

            case $this instanceof Node\Func\DateTime\Month:
                $this->assertTypes($args, [Type\Date::class], [Type\DateTimeOffset::class]);
                return Type\Int32::factory($arg0 ? $arg0->month : null);

            case $this instanceof Node\Func\DateTime\Now:
                return Type\DateTimeOffset::factory(Carbon::now());

            case $this instanceof Node\Func\DateTime\Second:
                $this->assertTypes($args, [Type\TimeOfDay::class], [Type\DateTimeOffset::class]);
                return Type\Int32::factory($arg0 ? $arg0->second : null);

            case $this instanceof Node\Func\DateTime\Time:
                $this->assertTypes($args, [Type\DateTimeOffset::class]);
                return Type\TimeOfDay::factory($arg0 ? $arg0->format(Type\TimeOfDay::dateFormat) : null);

            case $this instanceof Node\Func\DateTime\TotalOffsetMinutes:
                $this->assertTypes($args, [Type\DateTimeOffset::class]);
                return Type\Int32::factory($arg0 ? $arg0->utcOffset() : null);

            case $this instanceof Node\Func\DateTime\TotalSeconds:
                $this->assertTypes($args, [Type\Duration::class]);
                return Type\Decimal::factory($arg0);

            case $this instanceof Node\Func\DateTime\Year:
                $this->assertTypes($args, [Type\Date::class], [Type\DateTimeOffset::class]);
                return Type\Int32::factory($arg0 ? $arg0->year : null);

            // 5.1.1.9 Arithmetic functions
            case $this instanceof Node\Func\Arithmetic\Ceiling:
                $this->assertTypes($args, [Type\Numeric::class]);
                return $arg0 ? $args[0]::factory(ceil($arg0)) : null;

            case $this instanceof Node\Func\Arithmetic\Floor:
                $this->assertTypes($args, [Type\Numeric::class]);
                return $arg0 ? $args[0]::factory(floor($arg0)) : null;

            case $this instanceof Node\Func\Arithmetic\Round:
                $this->assertTypes($args, [Type\Numeric::class]);
                return $arg0 ? $args[0]::factory(round($arg0)) : null;
        }

        throw new NotImplementedException();
    }

    /**
     * Confirm the provided arguments fit one of the provided type maps
     * @param  array  $args  arguments
     * @param  string[]  ...$typeMaps  Typemaps
     * @return bool
     */
    public function ifTypes(array $args, array ...$typeMaps): bool
    {
        foreach ($typeMaps as $typeMap) {
            $matches = 0;

            for ($i = 0; $i < count($typeMap); $i++) {
                $arg = $args[$i] ?? null;
                if ($arg === null || $arg instanceof $typeMap[$i]) {
                    $matches++;
                }
            }

            if ($matches === count($typeMap)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Throw an exception if the provided types do not match any type map
     * @param  array  $args  Arguments
     * @param  string[]  ...$typeMaps  Typemaps
     */
    public function assertTypes(array $args, array ...$typeMaps): void
    {
        if ($this->ifTypes($args, ...$typeMaps)) {
            return;
        }

        throw new BadRequestException(
            'incompatible_types',
            'Incompatible types were provided for operation '.$this::symbol
        );
    }
}
