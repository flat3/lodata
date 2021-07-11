<?php

namespace Flat3\Lodata\Expression;

use Carbon\CarbonImmutable as Carbon;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
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
            $primitive = self::eval($arg, $entity);

            if ($primitive instanceof Primitive) {
                return $primitive->get();
            }

            return $primitive;
        }, $node->getArguments());

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
                return in_array($lValue, $args);

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

                return $lValue + $rValue;

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

                return $lValue - $rValue;

            case $node instanceof Operator\Arithmetic\Mul:
                return $lValue * $rValue;

            case $node instanceof Operator\Arithmetic\Div:
                return $lValue / $rValue;

            case $node instanceof Operator\Arithmetic\DivBy:
                return (float) $lValue / (float) $rValue;

            case $node instanceof Operator\Arithmetic\Mod:
                return $lValue % $rValue;

            // 5.1.1.5 String and Collection Functions
            case $node instanceof Node\Func\StringCollection\Concat:
                return join('', $args);

            case $node instanceof Node\Func\StringCollection\Contains:
                return Str::contains(...$args);

            case $node instanceof Node\Func\StringCollection\EndsWith:
                return Str::endsWith(...$args);

            case $node instanceof Node\Func\StringCollection\IndexOf:
                return strpos(...$args);

            case $node instanceof Node\Func\StringCollection\Length:
                return Str::length(...$args);

            case $node instanceof Node\Func\StringCollection\StartsWith:
                return Str::startsWith(...$args);

            case $node instanceof Node\Func\StringCollection\Substring:
                return substr(...$args);

            // 5.1.1.7 String functions
            case $node instanceof Node\Func\String\MatchesPattern:
                return 1 === preg_match('/'.$args[1].'/', $args[0]);

            case $node instanceof Node\Func\String\ToLower:
                return strtolower(...$args);

            case $node instanceof Node\Func\String\ToUpper:
                return strtoupper(...$args);

            case $node instanceof Node\Func\String\Trim:
                return trim(...$args);

            // 5.1.1.8 Date and time functions
            case $node instanceof Node\Func\DateTime\Date:
                return Type\Date::factory(Carbon::parse($args[0])->format(Type\Date::DATE_FORMAT));

            case $node instanceof Node\Func\DateTime\Day:
                return Carbon::parse($args[0])->day;

            case $node instanceof Node\Func\DateTime\Hour:
                return Carbon::parse($args[0])->hour;

            case $node instanceof Node\Func\DateTime\Minute:
                return Carbon::parse($args[0])->minute;

            case $node instanceof Node\Func\DateTime\Month:
                return Carbon::parse($args[0])->month;

            case $node instanceof Node\Func\DateTime\Second:
                return Carbon::parse($args[0])->second;

            case $node instanceof Node\Func\DateTime\Time:
                return Type\TimeOfDay::factory(Carbon::parse($args[0])->format(Type\TimeOfDay::DATE_FORMAT));

            case $node instanceof Node\Func\DateTime\Year:
                return Carbon::parse($args[0])->year;

            // 5.1.1.9 Arithmetic functions
            case $node instanceof Node\Func\Arithmetic\Ceiling:
                return ceil(...$args);

            case $node instanceof Node\Func\Arithmetic\Floor:
                return floor(...$args);

            case $node instanceof Node\Func\Arithmetic\Round:
                return round(...$args);
        }

        throw new NotImplementedException();
    }
}