<?php

namespace Flat3\Lodata\Expression;

use Carbon\CarbonImmutable as Carbon;
use Flat3\Lodata\Entity;
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
    public static function eval(Node $node, ?Entity $entity = null)
    {
        $leftP = !$node->getLeftNode() ?: self::eval($node->getLeftNode(), $entity);
        $rightP = !$node->getRightNode() ?: self::eval($node->getRightNode(), $entity);

        $left = $leftP instanceof Primitive ? $leftP->get() : $leftP;
        $right = $rightP instanceof Primitive ? $rightP->get() : $rightP;

        $args = array_map(function ($arg) use ($entity) {
            $primitive = self::eval($arg, $entity);

            if ($primitive instanceof Primitive) {
                return $primitive->get();
            }

            return $primitive;
        }, $node->getArguments());

        switch (true) {
            // Deserialization
            case $node instanceof Property:
                $propertyValue = $entity[$node->getValue()];
                return $propertyValue === null ? null : $propertyValue->getPrimitiveValue();

            case $node instanceof Literal:
                return $node->getValue();

            // 5.1.1.1 Logical operators
            case $node instanceof Operator\Logical\Equal:
                return $left == $right;

            case $node instanceof Operator\Logical\NotEqual:
                return $left != $right;

            case $node instanceof Operator\Logical\GreaterThan:
                return $left !== null && $right !== null && $left > $right;

            case $node instanceof Operator\Logical\GreaterThanOrEqual:
                return $left !== null && $right !== null && $left >= $right;

            case $node instanceof Operator\Logical\LessThan:
                return $left !== null && $right !== null && $left < $right;

            case $node instanceof Operator\Logical\LessThanOrEqual:
                return $left !== null && $right !== null && $left <= $right;

            case $node instanceof Operator\Comparison\And_:
                if (($left === null && $right === false) || ($left === false && $right === null)) {
                    return false;
                }

                if ($left === null || $right === null) {
                    return null;
                }

                return $left && $right;

            case $node instanceof Operator\Comparison\Or_:
                if (($left === null && $right === true) || ($left === true && $right === null)) {
                    return true;
                }

                if ($left === null || $right === null) {
                    return null;
                }

                return $left || $right;

            case $node instanceof Operator\Comparison\Not_:
                return $left === null ? null : !$left;

            case $node instanceof Operator\Logical\In:
                return in_array($left, $args);

            // 5.1.1.2 Arithmetic operators
            case $node instanceof Operator\Arithmetic\Add:
                switch (true) {
                    case $left === null || $right === null:
                        return null;

                    case $leftP instanceof Type\Duration && $rightP instanceof Type\Duration:
                        return Type\Duration::factory($left + $right);

                    case $leftP instanceof Type\Date && $rightP instanceof Type\Duration:
                        return Type\Date::factory($left->addSeconds($right));

                    case $leftP instanceof Type\Duration && $rightP instanceof Type\Date:
                        return Type\Date::factory($right->addSeconds($left));

                    case $leftP instanceof Type\Duration && $rightP instanceof Type\DateTimeOffset:
                        return Type\DateTimeOffset::factory($right->addSeconds($left));

                    case $leftP instanceof Type\DateTimeOffset && $rightP instanceof Type\Duration:
                        return Type\DateTimeOffset::factory($left->addSeconds($right));
                }

                return $left + $right;

            case $node instanceof Operator\Arithmetic\Sub:
                return ($left === null || $right === null) ? null : $left - $right;

            case $node instanceof Operator\Arithmetic\Mul:
                return ($left === null || $right === null) ? null : $left * $right;

            case $node instanceof Operator\Arithmetic\Div:
                return ($left === null || $right === null) ? null : $left / $right;

            case $node instanceof Operator\Arithmetic\DivBy:
                return ($left === null || $right === null) ? null : (float) $left / (float) $right;

            case $node instanceof Operator\Arithmetic\Mod:
                return ($left === null || $right === null) ? null : $left % $right;

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