<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Parser;

use Carbon\Carbon;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Parser;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type;
use Illuminate\Support\Str;
use TypeError;

/**
 * Common expression parser
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_BuiltinQueryFunctions
 * @package Flat3\Lodata\Expression\Parser
 */
abstract class Common extends Parser
{
    /**
     * Evaluate this node using internal logic
     * @param  Node  $node  Node
     * @param  Entity|null  $entity  Entity
     * @return Primitive|null
     */
    public static function evaluate(Node $node, ?Entity $entity = null): ?Primitive
    {
        $left = !$node->getLeftNode() ?: self::evaluate($node->getLeftNode(), $entity);
        $right = !$node->getRightNode() ?: self::evaluate($node->getRightNode(), $entity);

        $lValue = $left instanceof Primitive ? $left->get() : $left;
        $rValue = $right instanceof Primitive ? $right->get() : $right;

        $args = array_map(function (Node $arg) use ($entity) {
            return self::evaluate($arg, $entity);
        }, $node->getArguments());

        $argv = array_map(function (?Primitive $arg = null) {
            return $arg ? $arg->get() : null;
        }, $args);

        $arg0 = $args[0] ?? null;
        if ($arg0) {
            $arg0 = $arg0->get();
        }

        switch (true) {
            case $node instanceof Node\Operator\Logical\GreaterThan:
            case $node instanceof Node\Operator\Logical\GreaterThanOrEqual:
            case $node instanceof Node\Operator\Logical\LessThan:
            case $node instanceof Node\Operator\Logical\LessThanOrEqual:
                if ($lValue === null || $rValue === null) {
                    return Type\Boolean::false();
                }
                break;

            case $node instanceof Node\Operator\Arithmetic\Add:
            case $node instanceof Node\Operator\Arithmetic\Sub:
            case $node instanceof Node\Operator\Arithmetic\Mul:
            case $node instanceof Node\Operator\Arithmetic\Div:
            case $node instanceof Node\Operator\Arithmetic\DivBy:
            case $node instanceof Node\Operator\Arithmetic\Mod:
                if ($lValue === null || $rValue === null) {
                    return null;
                }
                break;

            case $node instanceof Node\Operator\Comparison\Not_:
                if ($lValue === null) {
                    return null;
                }
                break;

            // 5.1.1.4 Canonical Functions
            case $node instanceof Node\Func;
                if (in_array(null, $argv, true)) {
                    return null;
                }
                break;
        }

        switch (true) {
            // Deserialization
            case $node instanceof Node\Property:
                $propertyValue = $entity[$node->getValue()];

                if (null === $propertyValue) {
                    throw new BadRequestException(
                        'invalid_property',
                        sprintf('The property (%s) used in an expression did not exist', $node->getValue())
                    );
                }

                return $propertyValue->getPrimitive();

            case $node instanceof Node\Literal:
                return $node->getValue();

            // 5.1.1.1 Logical operators
            case $node instanceof Node\Operator\Logical\Equal:
                if (array_intersect([$lValue, $rValue], [null, INF, -INF])) {
                    return new Type\Boolean($lValue === $rValue);
                }

                if (self::ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return new Type\Boolean($lValue->equalTo($rValue));
                }

                return new Type\Boolean($lValue == $rValue);

            case $node instanceof Node\Operator\Logical\NotEqual:
                if (array_intersect([$lValue, $rValue], [null, INF, -INF])) {
                    return new Type\Boolean($lValue !== $rValue);
                }

                if (self::ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return new Type\Boolean($lValue->notEqualTo($rValue));
                }

                return new Type\Boolean($lValue != $rValue);

            case $node instanceof Node\Operator\Logical\GreaterThan:
                if (self::ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return new Type\Boolean($lValue->greaterThan($rValue));
                }

                return new Type\Boolean($lValue > $rValue);

            case $node instanceof Node\Operator\Logical\GreaterThanOrEqual:
                if (self::ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return new Type\Boolean($lValue->greaterThanOrEqualTo($rValue));
                }

                return new Type\Boolean($lValue >= $rValue);

            case $node instanceof Node\Operator\Logical\LessThan:
                if (self::ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return new Type\Boolean($lValue->lessThan($rValue));
                }

                return new Type\Boolean($lValue < $rValue);

            case $node instanceof Node\Operator\Logical\LessThanOrEqual:
                if (self::ifTypes([$left, $right], [Type\DateTimeOffset::class, Type\DateTimeOffset::class])) {
                    return new Type\Boolean($lValue->lessThanOrEqualTo($rValue));
                }

                return new Type\Boolean($lValue <= $rValue);

            case $node instanceof Node\Operator\Comparison\And_:
                if (($lValue === null && $rValue === false) || ($lValue === false && $rValue === null)) {
                    return Type\Boolean::false();
                }

                if ($lValue === null || $rValue === null) {
                    return null;
                }

                return new Type\Boolean($lValue && $rValue);

            case $node instanceof Node\Operator\Comparison\Or_:
                if (($lValue === null && $rValue === true) || ($lValue === true && $rValue === null)) {
                    return Type\Boolean::true();
                }

                if ($lValue === null || $rValue === null) {
                    return null;
                }

                return new Type\Boolean($lValue || $rValue);

            case $node instanceof Node\Operator\Comparison\Not_:
                return new Type\Boolean(!$lValue);

            case $node instanceof Node\Operator\Logical\In:
                return new Type\Boolean(in_array($lValue, $argv));

            case $node instanceof Node\Operator\Logical\Has:
                return new Type\Boolean($left->hasFlags($right->toFlags()));

            // 5.1.1.2 Arithmetic operators
            case $node instanceof Node\Operator\Arithmetic\Add:
                switch (true) {
                    case $left instanceof Type\Duration && $right instanceof Type\Duration:
                        return new Type\Duration($lValue + $rValue);

                    case $left instanceof Type\Date && $right instanceof Type\Duration:
                        return new Type\Date($lValue->addSeconds($rValue));

                    case $left instanceof Type\Duration && $right instanceof Type\Date:
                        return new Type\Date($rValue->addSeconds($lValue));

                    case $left instanceof Type\Duration && $right instanceof Type\DateTimeOffset:
                        return new Type\DateTimeOffset($rValue->addSeconds($lValue));

                    case $left instanceof Type\DateTimeOffset && $right instanceof Type\Duration:
                        return new Type\DateTimeOffset($lValue->addSeconds($rValue));
                }

                self::assertTypes($node, [$left, $right], [Type\Numeric::class, Type\Numeric::class]);

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

            case $node instanceof Node\Operator\Arithmetic\Sub:
                switch (true) {
                    case $left instanceof Type\Duration && $right instanceof Type\Duration:
                        return new Type\Duration($lValue - $rValue);

                    case $left instanceof Type\Date && $right instanceof Type\Duration:
                        return new Type\Date($lValue->subSeconds($rValue));

                    case $left instanceof Type\Duration && $right instanceof Type\Date:
                        return new Type\Date($rValue->subSeconds($lValue));

                    case $left instanceof Type\Duration && $right instanceof Type\DateTimeOffset:
                        return new Type\DateTimeOffset($rValue->subSeconds($lValue));

                    case $left instanceof Type\DateTimeOffset && $right instanceof Type\Duration:
                        return new Type\DateTimeOffset($lValue->subSeconds($rValue));
                }

                self::assertTypes($node, [$left, $right], [Type\Numeric::class, Type\Numeric::class]);

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

            case $node instanceof Node\Operator\Arithmetic\Mul:
                self::assertTypes(
                    $node,
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

            case $node instanceof Node\Operator\Arithmetic\Div:
                self::assertTypes(
                    $node,
                    [$left, $right],
                    [Type\Numeric::class, Type\Numeric::class],
                    [Type\Numeric::class, Type\Duration::class],
                    [Type\Duration::class, Type\Numeric::class]
                );

                if ($rValue == 0) {
                    self::assertTypes($node, [$left], [Type\Decimal::class]);

                    switch (true) {
                        case $lValue > 0:
                            return new Type\Decimal(INF);

                        case $lValue < 0:
                            return new Type\Decimal(-INF);

                        case $lValue == 0:
                            return new Type\Decimal(NAN);
                    }
                }

                $primitive = new Type\Decimal();

                switch (true) {
                    case $left instanceof Type\Duration || $right instanceof Type\Duration:
                        $primitive = new Type\Duration();
                        break;
                }

                if ($left instanceof Type\Byte && $right instanceof Type\Byte) {
                    return new Type\Int64($lValue / $rValue);
                }

                return $primitive->set($lValue / $rValue);

            case $node instanceof Node\Operator\Arithmetic\DivBy:
                self::assertTypes(
                    $node,
                    [$left, $right],
                    [Type\Numeric::class, Type\Numeric::class],
                    [Type\Numeric::class, Type\Duration::class],
                    [Type\Duration::class, Type\Numeric::class],
                );

                if ($rValue == 0) {
                    self::assertTypes($node, [$left], [Type\Decimal::class]);

                    switch (true) {
                        case $lValue > 0:
                            return new Type\Decimal(INF);

                        case $lValue < 0:
                            return new Type\Decimal(-INF);

                        case $lValue == 0:
                            return new Type\Decimal(NAN);
                    }
                }

                $primitive = new Type\Decimal();

                switch (true) {
                    case $left instanceof Type\Duration || $right instanceof Type\Duration:
                        $primitive = new Type\Duration();
                        break;
                }

                return $primitive->set((float) $lValue / (float) $rValue);

            case $node instanceof Node\Operator\Arithmetic\Mod:
                self::assertTypes($node, [$left, $right], [Type\Numeric::class, Type\Numeric::class]);

                if ($rValue == 0) {
                    throw new BadRequestException('division_by_zero', 'A division by zero was encountered');
                }

                return new Type\Double($lValue % $rValue);

            // 5.1.1.5 String and Collection Functions
            case $node instanceof Node\Func\StringCollection\Concat:
                self::assertTypes($node, $args, [Type\String_::class, Type\String_::class]);
                return new Type\String_(join('', $argv));

            case $node instanceof Node\Func\StringCollection\Contains:
                self::assertTypes($node, $args, [Type\String_::class, Type\String_::class]);
                return new Type\Boolean(Str::contains(...$argv));

            case $node instanceof Node\Func\StringCollection\EndsWith:
                self::assertTypes($node, $args, [Type\String_::class, Type\String_::class]);
                return new Type\Boolean(Str::endsWith(...$argv));

            case $node instanceof Node\Func\StringCollection\IndexOf:
                self::assertTypes($node, $args, [Type\String_::class, Type\String_::class]);
                $position = strpos(...$argv);
                return $position === false ? new Type\Int32(-1) : new Type\Int32($position);

            case $node instanceof Node\Func\StringCollection\Length:
                self::assertTypes($node, $args, [Type\String_::class]);
                return new Type\Int32(Str::length(...$argv));

            case $node instanceof Node\Func\StringCollection\StartsWith:
                self::assertTypes($node, $args, [Type\String_::class, Type\String_::class]);
                return new Type\Boolean(Str::startsWith(...$argv));

            case $node instanceof Node\Func\StringCollection\Substring:
                self::assertTypes(
                    $node,
                    $args,
                    [Type\String_::class, Type\Byte::class],
                    [Type\String_::class, Type\Byte::class, Type\Byte::class]
                );
                return new Type\String_(substr(...$argv));

            // 5.1.1.7 String functions
            case $node instanceof Node\Func\String\MatchesPattern:
                self::assertTypes($node, $args, [Type\String_::class, Type\String_::class]);
                return new Type\Boolean(1 === preg_match('/'.$argv[1].'/', $argv[0]));

            case $node instanceof Node\Func\String\ToLower:
                self::assertTypes($node, $args, [Type\String_::class]);
                return new Type\String_(strtolower(...$argv));

            case $node instanceof Node\Func\String\ToUpper:
                self::assertTypes($node, $args, [Type\String_::class]);
                return new Type\String_(strtoupper(...$argv));

            case $node instanceof Node\Func\String\Trim:
                self::assertTypes($node, $args, [Type\String_::class]);
                return new Type\String_(trim(...$argv));

            // 5.1.1.8 Date and time functions
            case $node instanceof Node\Func\DateTime\Date:
                self::assertTypes($node, $args, [Type\DateTimeOffset::class]);
                return new Type\Date($arg0 ? $arg0->format(Type\Date::dateFormat) : null);

            case $node instanceof Node\Func\DateTime\Day:
                self::assertTypes($node, $args, [Type\Date::class], [Type\DateTimeOffset::class]);
                return new Type\Int32($arg0 ? $arg0->day : null);

            case $node instanceof Node\Func\DateTime\FractionalSeconds:
                self::assertTypes($node, $args, [Type\TimeOfDay::class], [Type\DateTimeOffset::class]);
                return new Type\Decimal($arg0 ? $arg0->micro / 1000000 : null);

            case $node instanceof Node\Func\DateTime\Hour:
                self::assertTypes($node, $args, [Type\TimeOfDay::class], [Type\DateTimeOffset::class]);
                return new Type\Int32($arg0 ? $arg0->hour : null);

            case $node instanceof Node\Func\DateTime\MaxDateTime:
                return new Type\DateTimeOffset(Carbon::create(9999, 12, 31, 23, 59, 59));

            case $node instanceof Node\Func\DateTime\MinDateTime:
                return new Type\DateTimeOffset(Carbon::create(1));

            case $node instanceof Node\Func\DateTime\Minute:
                self::assertTypes($node, $args, [Type\TimeOfDay::class], [Type\DateTimeOffset::class]);
                return new Type\Int32($arg0 ? $arg0->minute : null);

            case $node instanceof Node\Func\DateTime\Month:
                self::assertTypes($node, $args, [Type\Date::class], [Type\DateTimeOffset::class]);
                return new Type\Int32($arg0 ? $arg0->month : null);

            case $node instanceof Node\Func\DateTime\Now:
                return new Type\DateTimeOffset(Carbon::now());

            case $node instanceof Node\Func\DateTime\Second:
                self::assertTypes($node, $args, [Type\TimeOfDay::class], [Type\DateTimeOffset::class]);
                return new Type\Int32($arg0 ? $arg0->second : null);

            case $node instanceof Node\Func\DateTime\Time:
                self::assertTypes($node, $args, [Type\DateTimeOffset::class]);
                return new Type\TimeOfDay($arg0 ? $arg0->format(Type\TimeOfDay::dateFormat) : null);

            case $node instanceof Node\Func\DateTime\TotalOffsetMinutes:
                self::assertTypes($node, $args, [Type\DateTimeOffset::class]);
                return new Type\Int32($arg0 ? $arg0->utcOffset() : null);

            case $node instanceof Node\Func\DateTime\TotalSeconds:
                self::assertTypes($node, $args, [Type\Duration::class]);
                return new Type\Decimal($arg0);

            case $node instanceof Node\Func\DateTime\Year:
                self::assertTypes($node, $args, [Type\Date::class], [Type\DateTimeOffset::class]);
                return new Type\Int32($arg0 ? $arg0->year : null);

            // 5.1.1.9 Arithmetic functions
            case $node instanceof Node\Func\Arithmetic\Ceiling:
                self::assertTypes($node, $args, [Type\Numeric::class]);
                return $arg0 ? new $args[0](ceil($arg0)) : null;

            case $node instanceof Node\Func\Arithmetic\Floor:
                self::assertTypes($node, $args, [Type\Numeric::class]);
                return $arg0 ? new $args[0](floor($arg0)) : null;

            case $node instanceof Node\Func\Arithmetic\Round:
                self::assertTypes($node, $args, [Type\Numeric::class]);
                return $arg0 ? new $args[0](round($arg0)) : null;

            // 5.1.1.10 Type functions
            case $node instanceof Node\Func\Type\Cast:
                self::assertTypes($node, $args, [Primitive::class, Type\String_::class]);

                foreach ([
                             Type\Binary::class, Type\Boolean::class, Type\Byte::class, Type\Date::class,
                             Type\DateTimeOffset::class, Type\Decimal::class, Type\Double::class, Type\Duration::class,
                             Type\Guid::class, Type\Int16::class, Type\Int32::class, Type\Int64::class,
                             Type\SByte::class, Type\Single::class, Type\String_::class, Type\TimeOfDay::class,
                             Type\UInt16::class, Type\UInt32::class, Type\UInt64::class,
                         ] as $type) {
                    if ($type::identifier === $argv[1]) {
                        try {
                            return new $type($arg0);
                        } catch (TypeError $e) {
                            return null;
                        }
                    }
                }
        }

        throw new NotImplementedException();
    }

    /**
     * Throw an exception if the provided types do not match any type map
     * @param  array  $args  Arguments
     * @param  string[]  ...$typeMaps  Typemaps
     */
    public static function assertTypes(Node $node, array $args, array ...$typeMaps): void
    {
        if (self::ifTypes($args, ...$typeMaps)) {
            return;
        }

        throw new BadRequestException(
            'incompatible_types',
            'Incompatible types were provided for operation '.$node::symbol
        );
    }

    /**
     * Confirm the provided arguments fit one of the provided type maps
     * @param  array  $args  arguments
     * @param  string[]  ...$typeMaps  Typemaps
     * @return bool
     */
    public static function ifTypes(array $args, array ...$typeMaps): bool
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
}