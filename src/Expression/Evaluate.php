<?php

namespace Flat3\Lodata\Expression;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Operator;
use Flat3\Lodata\Expression\Node\Property;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type;
use Illuminate\Support\Str;

/**
 * Class Evaluate
 * Use built-in functions to evaluate an expression
 * @package Flat3\Lodata\Expression
 */
class Evaluate
{
    public static function incompatible(Node $node)
    {
        throw new BadRequestException(
            'incompatible_types',
            'Incompatible types were provided for operation '.$node::symbol
        );
    }

    public static function eval(Node $node, ?Entity $entity = null)
    {
        $left = !$node->getLeftNode() ?: self::eval($node->getLeftNode(), $entity);
        $right = !$node->getRightNode() ?: self::eval($node->getRightNode(), $entity);

        $lValue = $left instanceof Primitive ? $left->get() : $left;
        $rValue = $right instanceof Primitive ? $right->get() : $right;

        $args = array_map(function ($arg) use ($entity) {
            return self::eval($arg, $entity);
        }, $node->getArguments());

        $argv = array_map(function (?Primitive $arg = null) {
            return $arg ? $arg->get() : null;
        }, $args);

        $arg0 = $args[0] ?? null;

        switch (true) {
            case $node instanceof Operator\Logical\GreaterThan:
            case $node instanceof Operator\Logical\GreaterThanOrEqual:
            case $node instanceof Operator\Logical\LessThan:
            case $node instanceof Operator\Logical\LessThanOrEqual:
                if ($lValue === null || $rValue === null) {
                    return false;
                }
                break;

            case $node instanceof Operator\Arithmetic\Add:
            case $node instanceof Operator\Arithmetic\Sub:
            case $node instanceof Operator\Arithmetic\Mul:
            case $node instanceof Operator\Arithmetic\Div:
            case $node instanceof Operator\Arithmetic\DivBy:
            case $node instanceof Operator\Arithmetic\Mod:
                if ($lValue === null || $rValue === null) {
                    return null;
                }
                break;

            case $node instanceof Operator\Comparison\Not_:
                if ($lValue === null) {
                    return null;
                }
                break;
        }

        switch (true) {
            // Deserialization
            case $node instanceof Property:
                $propertyValue = $entity[$node->getValue()];
                return $propertyValue === null ? null : $propertyValue->getPrimitiveValue();

            case $node instanceof Literal:
                return $node->getValue();

            // 5.1.1.1 Logical operators
            case $node instanceof Operator\Logical\Equal:
                switch (true) {
                    case $left instanceof Type\DateTimeOffset:
                    case $right instanceof Type\DateTimeOffset:
                        if (!$left instanceof Type\DateTimeOffset || !$right instanceof Type\DateTimeOffset) {
                            self::incompatible($node);
                        }

                        if ($lValue === null || $rValue === null) {
                            return $lValue === $rValue;
                        }

                        return $lValue->equalTo($rValue);
                }

                return $lValue == $rValue;

            case $node instanceof Operator\Logical\NotEqual:
                switch (true) {
                    case $left instanceof Type\DateTimeOffset:
                    case $right instanceof Type\DateTimeOffset:
                        if (!$left instanceof Type\DateTimeOffset || !$right instanceof Type\DateTimeOffset) {
                            self::incompatible($node);
                        }

                        return $lValue->notEqualTo($rValue);
                }

                return $lValue != $rValue;

            case $node instanceof Operator\Logical\GreaterThan:
                switch (true) {
                    case $left instanceof Type\DateTimeOffset:
                    case $right instanceof Type\DateTimeOffset:
                        if (!$left instanceof Type\DateTimeOffset || !$right instanceof Type\DateTimeOffset) {
                            self::incompatible($node);
                        }

                        return $lValue->greaterThan($rValue);
                }

                return $lValue > $rValue;

            case $node instanceof Operator\Logical\GreaterThanOrEqual:
                switch (true) {
                    case $left instanceof Type\DateTimeOffset:
                    case $right instanceof Type\DateTimeOffset:
                        if (!$left instanceof Type\DateTimeOffset || !$right instanceof Type\DateTimeOffset) {
                            self::incompatible($node);
                        }

                        return $lValue->greaterThanOrEqualTo($rValue);
                }

                return $lValue >= $rValue;

            case $node instanceof Operator\Logical\LessThan:
                switch (true) {
                    case $left instanceof Type\DateTimeOffset:
                    case $right instanceof Type\DateTimeOffset:
                        if (!$left instanceof Type\DateTimeOffset || !$right instanceof Type\DateTimeOffset) {
                            self::incompatible($node);
                        }

                        return $lValue->lessThan($rValue);
                }

                return $lValue < $rValue;

            case $node instanceof Operator\Logical\LessThanOrEqual:
                switch (true) {
                    case $left instanceof Type\DateTimeOffset:
                    case $right instanceof Type\DateTimeOffset:
                        if (!$left instanceof Type\DateTimeOffset || !$right instanceof Type\DateTimeOffset) {
                            self::incompatible($node);
                        }

                        return $lValue->lessThanOrEqualTo($rValue);
                }

                return $lValue <= $rValue;

            case $node instanceof Operator\Comparison\And_:
                if (($lValue === null && $rValue === false) || ($lValue === false && $rValue === null)) {
                    return false;
                }

                if ($lValue === null || $rValue === null) {
                    return null;
                }

                return $lValue && $rValue;

            case $node instanceof Operator\Comparison\Or_:
                if (($lValue === null && $rValue === true) || ($lValue === true && $rValue === null)) {
                    return true;
                }

                if ($lValue === null || $rValue === null) {
                    return null;
                }

                return $lValue || $rValue;

            case $node instanceof Operator\Comparison\Not_:
                return !$lValue;

            case $node instanceof Operator\Logical\In:
                return in_array($lValue, $argv);

            // 5.1.1.2 Arithmetic operators
            case $node instanceof Operator\Arithmetic\Add:
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

                    case !$left instanceof Type\Numeric || !$right instanceof Type\Numeric:
                        throw new BadRequestException(
                            'incompatible_types',
                            'Incompatible types were provided for operation'
                        );
                }

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

            case $node instanceof Operator\Arithmetic\Sub:
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

                    case !$left instanceof Type\Numeric || !$right instanceof Type\Numeric:
                        throw new BadRequestException(
                            'incompatible_types',
                            'Incompatible types were provided for operation'
                        );
                }

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

            case $node instanceof Operator\Arithmetic\Mul:
                if (!(
                    ($left instanceof Type\Numeric && $right instanceof Type\Numeric) ||
                    ($left instanceof Type\Numeric && $right instanceof Type\Duration) ||
                    ($left instanceof Type\Duration && $right instanceof Type\Numeric)
                )) {
                    throw new BadRequestException(
                        'incompatible_types',
                        'Incompatible types were provided for operation'
                    );
                }

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

            case $node instanceof Operator\Arithmetic\Div:
                if (!(
                    ($left instanceof Type\Numeric && $right instanceof Type\Numeric) ||
                    ($left instanceof Type\Numeric && $right instanceof Type\Duration) ||
                    ($left instanceof Type\Duration && $right instanceof Type\Numeric)
                )) {
                    throw new BadRequestException(
                        'incompatible_types',
                        'Incompatible types were provided for operation'
                    );
                }

                if ($rValue == 0) {
                    if (!$left instanceof Type\Decimal) {
                        throw new BadRequestException('division_by_zero', 'A division by zero was encountered');
                    }

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

            case $node instanceof Operator\Arithmetic\DivBy:
                if (!(
                    ($left instanceof Type\Numeric && $right instanceof Type\Numeric) ||
                    ($left instanceof Type\Numeric && $right instanceof Type\Duration) ||
                    ($left instanceof Type\Duration && $right instanceof Type\Numeric)
                )) {
                    throw new BadRequestException(
                        'incompatible_types',
                        'Incompatible types were provided for operation'
                    );
                }

                if ($rValue == 0) {
                    if (!$left instanceof Type\Decimal) {
                        throw new BadRequestException('division_by_zero', 'A division by zero was encountered');
                    }

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

            case $node instanceof Operator\Arithmetic\Mod:
                if (!($left instanceof Type\Numeric && $right instanceof Type\Numeric)) {
                    throw new BadRequestException(
                        'incompatible_types',
                        'Incompatible types were provided for operation'
                    );
                }

                if ($rValue == 0) {
                    throw new BadRequestException('division_by_zero', 'A division by zero was encountered');
                }

                return Type\Double::factory($lValue % $rValue);

            // 5.1.1.5 String and Collection Functions
            case $node instanceof Node\Func\StringCollection\Concat:
                return join('', $argv);

            case $node instanceof Node\Func\StringCollection\Contains:
                return Str::contains(...$argv);

            case $node instanceof Node\Func\StringCollection\EndsWith:
                return Str::endsWith(...$argv);

            case $node instanceof Node\Func\StringCollection\IndexOf:
                return strpos(...$argv);

            case $node instanceof Node\Func\StringCollection\Length:
                return Str::length(...$argv);

            case $node instanceof Node\Func\StringCollection\StartsWith:
                return Str::startsWith(...$argv);

            case $node instanceof Node\Func\StringCollection\Substring:
                return substr(...$argv);

            // 5.1.1.7 String functions
            case $node instanceof Node\Func\String\MatchesPattern:
                return 1 === preg_match('/'.$argv[1].'/', $argv[0]);

            case $node instanceof Node\Func\String\ToLower:
                return strtolower(...$argv);

            case $node instanceof Node\Func\String\ToUpper:
                return strtoupper(...$argv);

            case $node instanceof Node\Func\String\Trim:
                return trim(...$argv);

            // 5.1.1.8 Date and time functions
            case $node instanceof Node\Func\DateTime\Date:
                return Type\Date::factory($arg0 ? $arg0->get()->format(Type\Date::DATE_FORMAT) : null);

            case $node instanceof Node\Func\DateTime\Day:
                return Type\Int32::factory($arg0 ? $arg0->get()->day : null);

            case $node instanceof Node\Func\DateTime\Hour:
                return Type\Int32::factory($arg0 ? $arg0->get()->hour : null);

            case $node instanceof Node\Func\DateTime\Minute:
                return Type\Int32::factory($arg0 ? $arg0->get()->minute : null);

            case $node instanceof Node\Func\DateTime\Month:
                return Type\Int32::factory($arg0 ? $arg0->get()->month : null);

            case $node instanceof Node\Func\DateTime\Second:
                return Type\Int32::factory($arg0 ? $arg0->get()->second : null);

            case $node instanceof Node\Func\DateTime\Time:
                return Type\TimeOfDay::factory($arg0 ? $arg0->get()->format(Type\TimeOfDay::DATE_FORMAT) : null);

            case $node instanceof Node\Func\DateTime\Year:
                return Type\Int32::factory($arg0 ? $arg0->get()->year : null);

            // 5.1.1.9 Arithmetic functions
            case $node instanceof Node\Func\Arithmetic\Ceiling:
                return ceil(...$argv);

            case $node instanceof Node\Func\Arithmetic\Floor:
                return floor(...$argv);

            case $node instanceof Node\Func\Arithmetic\Round:
                return round(...$argv);
        }

        throw new NotImplementedException();
    }
}