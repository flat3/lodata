<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Func;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Ceiling;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Floor;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Round;
use Flat3\Lodata\Expression\Node\Func\DateTime\Day;
use Flat3\Lodata\Expression\Node\Func\DateTime\Hour;
use Flat3\Lodata\Expression\Node\Func\DateTime\Minute;
use Flat3\Lodata\Expression\Node\Func\DateTime\Month;
use Flat3\Lodata\Expression\Node\Func\DateTime\Now;
use Flat3\Lodata\Expression\Node\Func\DateTime\Second;
use Flat3\Lodata\Expression\Node\Func\DateTime\Time;
use Flat3\Lodata\Expression\Node\Func\DateTime\Year;
use Flat3\Lodata\Expression\Node\Func\String\MatchesPattern;
use Flat3\Lodata\Expression\Node\Func\String\ToLower;
use Flat3\Lodata\Expression\Node\Func\String\ToUpper;
use Flat3\Lodata\Expression\Node\Func\String\Trim;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Concat;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Contains;
use Flat3\Lodata\Expression\Node\Func\StringCollection\EndsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\IndexOf;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Length;
use Flat3\Lodata\Expression\Node\Func\StringCollection\StartsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Substring;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Literal\Boolean;
use Flat3\Lodata\Expression\Node\Literal\Date;
use Flat3\Lodata\Expression\Node\Literal\DateTimeOffset;
use Flat3\Lodata\Expression\Node\Literal\Double;
use Flat3\Lodata\Expression\Node\Literal\Duration;
use Flat3\Lodata\Expression\Node\Literal\TimeOfDay;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Add;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Div;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\DivBy;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Mod;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Mul;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Sub;
use Flat3\Lodata\Expression\Node\Operator\Comparison;
use Flat3\Lodata\Expression\Node\Operator\Comparison\And_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Or_;
use Flat3\Lodata\Expression\Node\Operator\Lambda;
use Flat3\Lodata\Expression\Node\Operator\Logical\Equal;
use Flat3\Lodata\Expression\Node\Operator\Logical\GreaterThan;
use Flat3\Lodata\Expression\Node\Operator\Logical\GreaterThanOrEqual;
use Flat3\Lodata\Expression\Node\Operator\Logical\In;
use Flat3\Lodata\Expression\Node\Operator\Logical\LessThan;
use Flat3\Lodata\Expression\Node\Operator\Logical\LessThanOrEqual;
use Flat3\Lodata\Expression\Node\Operator\Logical\NotEqual;
use Flat3\Lodata\Expression\Node\Property;
use Flat3\Lodata\Expression\Operator;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\ReferentialConstraint;

/**
 * SQL Expression, with its associated parameters
 * @package Flat3\Lodata\Drivers\SQL
 */
class SQLExpression
{
    /** @var string $statement */
    protected $statement = '';

    /** @var string[] $parameters */
    protected $parameters = [];

    /** @var SQLEntitySet|EloquentEntitySet $entitySet */
    protected $entitySet = null;

    public function __construct(EntitySet $entitySet)
    {
        $this->entitySet = $entitySet;
    }

    /**
     * Evaluate the provided tree into an SQL expression
     * @param  Node  $node
     * @return void
     */
    public function evaluate(Node $node): void
    {
        switch (true) {
            case $node instanceof Func:
                $this->functionExpression($node);
                break;

            case $node instanceof Lambda:
                $this->lambdaExpression($node);
                break;

            case $node instanceof Property:
                $this->propertyExpression($node);
                break;

            case $node instanceof Literal:
                $this->literalExpression($node);
                break;

            case $node instanceof Operator:
                $this->operatorExpression($node);
                break;
        }
    }

    /**
     * Get the contained expression
     * @return string
     */
    public function getStatement(): string
    {
        return $this->statement;
    }

    /**
     * Get the contained expression parameters
     * @return int[]|string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Push an expression statement into the buffer
     * @param  string  $statement  Expression
     * @return $this
     */
    public function pushStatement(string $statement): self
    {
        $this->statement = $this->statement ? $this->statement.' '.$statement : $statement;

        return $this;
    }

    /**
     * Push a comma into the buffer
     * @return $this
     */
    public function pushComma(): self
    {
        $this->statement .= ',';

        return $this;
    }

    /**
     * Push one or more parameters into the buffer
     * @param  string|string[]  $parameter  Parameters
     * @return $this
     */
    public function pushParameter($parameter): self
    {
        $this->parameters = array_merge($this->parameters, is_array($parameter) ? $parameter : [$parameter]);

        return $this;
    }

    /**
     * Merge the content of another container into this one
     * @param  SQLExpression  $expression  Container
     * @return $this
     */
    public function pushExpression(SQLExpression $expression): self
    {
        if (!$expression->hasStatement()) {
            return $this;
        }

        $this->pushStatement($expression->getStatement());
        $this->pushParameter($expression->getParameters());

        return $this;
    }

    /**
     * Return whether or not this container has an expression statement
     * @return bool
     */
    public function hasStatement(): bool
    {
        return !!$this->statement;
    }

    /**
     * Expand an operator expression
     * @param  Operator  $node  Node
     * @return void
     */
    protected function operatorExpression(Operator $node): void
    {
        $driver = $this->entitySet->getDriver();
        $left = $node->getLeftNode();
        $right = $node->getRightNode();

        switch (true) {
            case $node instanceof Not_:
                $this->pushStatement('(');
                $this->pushStatement('NOT');
                $this->evaluate($left);
                $this->pushStatement(')');
                return;

            case $node instanceof In:
                $this->evaluate($left);
                $this->pushStatement('IN');
                $this->pushStatement('(');
                $this->addCommaSeparatedArguments($node);
                $this->pushStatement(')');
                return;
        }

        if ($driver === SQLEntitySet::PostgreSQL && ($node instanceof Div || $node instanceof Mod)) {
            switch (true) {
                case $node instanceof Div:
                    $this->pushStatement('DIV(');
                    break;

                case $node instanceof Mod:
                    $this->pushStatement('MOD(');
                    break;
            }

            $this->pushStatement('CAST(');
            $this->evaluate($left);
            $this->pushStatement('AS NUMERIC )');
            $this->pushComma();
            $this->pushStatement('CAST(');
            $this->evaluate($right);
            $this->pushStatement('AS NUMERIC )');
            $this->pushStatement(')');
            return;
        }

        $this->pushStatement('(');
        $this->evaluate($left);

        if (
            !$node instanceof Comparison
            && (
                $left instanceof StartsWith
                || $left instanceof EndsWith
                || $left instanceof Contains
                || $right instanceof StartsWith
                || $right instanceof EndsWith
                || $right instanceof Contains
            )
        ) {
            if (!($node instanceof Equal && $right instanceof Boolean && $right->getValue()->get() === true)) {
                throw new BadRequestException(
                    'This entity set does not support expression operators with startswith, endswith, contains other than x eq true'
                );
            }

            $this->pushStatement(')');
            return;
        }

        switch (true) {
            case $node instanceof Div:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                        $this->pushStatement('DIV');
                        break;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Add:
                $this->pushStatement('+');
                break;

            case $node instanceof DivBy:
                $this->pushStatement('/');
                break;

            case $node instanceof Mod:
                switch ($driver) {
                    case SQLEntitySet::SQLServer:
                        $node->notImplemented();

                    default:
                        $this->pushStatement('%');
                        break;
                }
                break;

            case $node instanceof Mul:
                $this->pushStatement('*');
                break;

            case $node instanceof Sub:
                $this->pushStatement('-');
                break;

            case $node instanceof And_:
                $this->pushStatement('AND');
                break;

            case $node instanceof Or_:
                $this->pushStatement('OR');
                break;

            case $node instanceof Equal:
                if ($right instanceof Literal && $right->getValue() === null) {
                    $this->pushStatement('IS NULL )');
                    return;
                }

                $this->pushStatement('=');
                break;

            case $node instanceof GreaterThan:
                $this->pushStatement('>');
                break;

            case $node instanceof GreaterThanOrEqual:
                $this->pushStatement('>=');
                break;

            case $node instanceof LessThan:
                $this->pushStatement('<');
                break;

            case $node instanceof LessThanOrEqual:
                $this->pushStatement('<=');
                break;

            case $node instanceof NotEqual:
                if ($right instanceof Literal && $right->getValue() === null) {
                    $this->pushStatement('IS NOT NULL )');
                    return;
                }

                $this->pushStatement('!=');
                break;
        }

        $this->evaluate($right);
        $this->pushStatement(')');
    }

    /**
     * Expand a function expression
     * @param  Func  $node  Node
     * @return void
     */
    protected function functionExpression(Func $node): void
    {
        $driver = $this->entitySet->getDriver();
        $node->validateArguments();

        switch (true) {
            case $node instanceof Ceiling:
                $this->pushStatement('CEILING(');
                break;

            case $node instanceof Floor:
                $this->pushStatement('FLOOR(');
                break;

            case $node instanceof Round:
                $this->pushStatement('ROUND(');

                switch ($driver) {
                    case SQLEntitySet::SQLServer:
                        $this->evaluate($node->getArgument());
                        $this->pushComma();
                        $this->pushStatement('0 )');
                        return;
                }
                break;

            case $node instanceof Node\Func\DateTime\Date:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                        $this->pushStatement('DATE(');
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("STRFTIME( '%Y-%m-%d',");
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement('(');
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(')::date');
                        return;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('FORMAT(');
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(", 'yyyy-MM-dd')");
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Day:
                switch ($driver) {
                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('DATEPART( day,');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement("DATE_PART( 'DAY',");
                        break;

                    case SQLEntitySet::MySQL:
                        $this->pushStatement('DAY(');
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("CAST( STRFTIME( '%d',");
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(') AS NUMERIC )');
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Hour:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                        $this->pushStatement('HOUR(');
                        break;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('DATEPART( hour,');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement("DATE_PART( 'HOUR',");
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("CAST( STRFTIME( '%H',");
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(') AS NUMERIC )');
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Minute:
                switch ($driver) {
                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('DATEPART( minute,');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement("DATE_PART( 'MINUTE',");
                        break;

                    case SQLEntitySet::MySQL:
                        $this->pushStatement('MINUTE(');
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("CAST( STRFTIME( '%M',");
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(') AS NUMERIC )');
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Month:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                        $this->pushStatement('MONTH(');
                        break;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('DATEPART( month,');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement("DATE_PART( 'MONTH',");
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("CAST( STRFTIME( '%m',");
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(') AS NUMERIC )');
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Now:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement('NOW(');
                        break;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('CURRENT_TIMESTAMP(');
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("DATETIME( 'now'");
                        break;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Second:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                        $this->pushStatement('SECOND(');
                        break;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('DATEPART( second,');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement("DATE_PART( 'SECOND',");
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("CAST( STRFTIME( '%S',");
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(') AS NUMERIC )');
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Time:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                        $this->pushStatement('TIME(');
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("STRFTIME( '%H:%M:%S',");
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement('(');
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(')::time');
                        return;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('FORMAT(');
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(", 'HH:mm:ss')");
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Year:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                        $this->pushStatement('YEAR(');
                        break;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('DATEPART( year,');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement("DATE_PART( 'YEAR',");
                        break;

                    case SQLEntitySet::SQLite:
                        $this->pushStatement("CAST( STRFTIME( '%Y',");
                        $this->evaluate($node->getArgument());
                        $this->pushStatement(') AS NUMERIC )');
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof MatchesPattern:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                        $this->pushStatement('REGEXP_LIKE(');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $arguments = $node->getArguments();
                        list($arg1, $arg2) = $arguments;
                        $this->evaluate($arg1);
                        $this->pushStatement('~');
                        $this->evaluate($arg2);
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof ToLower:
                $this->pushStatement('LOWER(');
                break;

            case $node instanceof ToUpper:
                $this->pushStatement('UPPER(');
                break;

            case $node instanceof Trim:
                $this->pushStatement('TRIM(');
                break;

            case $node instanceof Concat:
                switch ($driver) {
                    case SQLEntitySet::PostgreSQL:
                        $arguments = $node->getArguments();

                        if (!$arguments) {
                            return;
                        }

                        $this->pushStatement('CONCAT(');
                        while ($arguments) {
                            $argument = array_shift($arguments);
                            $this->pushStatement('CAST(');
                            $this->evaluate($argument);
                            $this->pushStatement('AS TEXT )');

                            if ($arguments) {
                                $this->pushComma();
                            }
                        }

                        $this->pushStatement(')');
                        return;

                    case SQLEntitySet::MySQL:
                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('CONCAT(');
                        break;

                    case SQLEntitySet::SQLite:
                        $arguments = $node->getArguments();

                        if (!$arguments) {
                            return;
                        }

                        $this->pushStatement('(');

                        while ($arguments) {
                            $argument = array_shift($arguments);
                            $this->evaluate($argument);

                            if ($arguments) {
                                $this->pushStatement('||');
                            }
                        }

                        $this->pushStatement(')');
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof IndexOf:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                    case SQLEntitySet::SQLite:
                        $this->pushStatement('INSTR(');
                        break;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('CHARINDEX(');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $arguments = $node->getArguments();
                        list($arg1, $arg2) = $arguments;
                        $this->pushStatement('POSITION(');
                        $this->evaluate($arg1);
                        $this->pushStatement('IN');
                        $this->evaluate($arg2);
                        $this->pushStatement(')');
                        return;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Length:
                switch ($driver) {
                    case SQLEntitySet::SQLite:
                    case SQLEntitySet::MySQL:
                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement('LENGTH(');
                        break;

                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('LEN(');
                        break;

                    default:
                        $node->notImplemented();
                }
                break;

            case $node instanceof Substring:
                switch ($driver) {
                    case SQLEntitySet::MySQL:
                    case SQLEntitySet::SQLite:
                    case SQLEntitySet::SQLServer:
                        $this->pushStatement('SUBSTRING(');
                        break;

                    case SQLEntitySet::PostgreSQL:
                        $this->pushStatement('SUBSTR(');
                        break;

                    default:
                        $node->notImplemented();
                }

                list($arg1, $arg2, $arg3) = array_pad($node->getArguments(), 3, null);

                $this->evaluate($arg1);
                $this->pushComma();
                $this->pushStatement('(');
                $this->evaluate($arg2);
                $this->pushStatement('+ 1 )');
                $this->pushComma();

                if ($arg3) {
                    $this->evaluate($arg3);
                } else {
                    $this->pushStatement('2147483647');
                }

                $this->pushStatement(')');
                return;

            case $node instanceof Contains:
            case $node instanceof EndsWith:
            case $node instanceof StartsWith:
                $arguments = $node->getArguments();
                list($arg1, $arg2) = $arguments;

                $this->evaluate($arg1);
                $this->pushStatement('LIKE');
                $value = $arg2->getValue();

                if ($node instanceof StartsWith || $node instanceof Contains) {
                    $value .= '%';
                }

                if ($node instanceof EndsWith || $node instanceof Contains) {
                    $value = '%'.$value;
                }

                $arg2->setValue($value);
                $this->evaluate($arg2);
                return;
        }

        $this->addCommaSeparatedArguments($node);
        $this->pushStatement(')');
    }

    /**
     * Expand a property expression
     * @param  Property  $node  Node
     * @return void
     */
    protected function propertyExpression(Property $node): void
    {
        /** @var \Flat3\Lodata\Property $property */
        $property = null;

        switch (true) {
            case $node instanceof Property\Navigation:
            case $node instanceof Property\Lambda:
            case $node instanceof Property\Declared:
                $type = $this->entitySet->getType();

                /** @var DeclaredProperty $property */
                $property = $type->getProperty($node->getValue());
                break;

            case $node instanceof Property\Computed:
                $property = $this->entitySet->getCompute()->getProperties()->get($node->getValue());
                break;
        }

        if (!$property || !$property->isFilterable()) {
            throw new BadRequestException(
                sprintf('The provided property (%s) is not filterable', $property->getName())
            );
        }

        $expression = $this->entitySet->propertyToExpression($property);
        $this->pushExpression($expression);
    }

    /**
     * Add comma separated arguments to the expression
     * @param  Node  $node
     * @return void
     */
    protected function addCommaSeparatedArguments(Node $node)
    {
        $arguments = $node->getArguments();

        while ($arguments) {
            $arg = array_shift($arguments);
            $this->evaluate($arg);

            if ($arguments) {
                $this->pushComma();
            }
        }
    }

    /**
     * Expand a literal into the expression
     * @param  Literal  $node  Node
     * @return void
     */
    protected function literalExpression(Literal $node): void
    {
        if ($node->getValue() === null) {
            $this->pushStatement('NULL');
            return;
        }

        switch (true) {
            case $node instanceof Boolean:
                $this->pushStatement('?');
                $this->pushParameter((int) $node->getValue()->get());
                break;

            case $node instanceof Date:
                $this->pushStatement('?');
                $this->pushParameter($node->getValue()->get()->format('Y-m-d'));
                break;

            case $node instanceof Duration:
                $this->pushStatement('?');
                $this->pushParameter($node->getValue()->get());
                break;

            case $node instanceof DateTimeOffset:
                $this->pushStatement('?');
                $this->pushParameter($node->getValue()->get()->format('Y-m-d H:i:s'));
                break;

            case $node instanceof TimeOfDay:
                $this->pushStatement('?');
                $this->pushParameter($node->getValue()->get()->format('H:i:s'));
                break;

            case $node instanceof Double:
                $value = $node->getValue();

                switch ($this->entitySet->getDriver()) {
                    case SQLEntitySet::SQLite:
                        $this->pushStatement('CAST( ? AS NUMERIC )');
                        break;

                    default:
                        $this->pushStatement('?');
                        break;
                }

                $this->pushParameter($value->get());
                break;

            default:
                $value = $node->getValue();
                $this->pushStatement('?');
                $this->pushParameter($value->get());
                break;
        }
    }

    /**
     * Expand a lambda expression
     * @param  Lambda  $node  Node
     * @return void
     */
    protected function lambdaExpression(Lambda $node): void
    {
        $driver = $this->entitySet->getDriver();

        if ($driver === SQLEntitySet::SQLite) {
            $node->notImplemented();
        }

        list ($lambdaExpression) = $node->getArguments();

        /** @var NavigationProperty $navigationProperty */
        $navigationProperty = $node->getNavigationProperty()->getValue();

        /** @var NavigationBinding $navigationBinding */
        $navigationBinding = $this->entitySet->getBindingByNavigationProperty($navigationProperty);

        /** @var SQLEntitySet $targetSet */
        $targetSet = $navigationBinding->getTarget();

        /** @var ReferentialConstraint[] $constraints */
        $constraints = $navigationBinding->getPath()->getConstraints()->all();

        $parser = $lambdaExpression->getParser();

        while ($constraints) {
            $constraint = array_shift($constraints);

            $field = $this->entitySet->propertyToExpression($constraint->getProperty());
            $this->pushParameter($field->getParameters());

            $this->pushStatement(
                sprintf('( %s = %s ( SELECT %s from %s WHERE',
                    $field->getStatement(),
                    $node instanceof Lambda\Any ? 'ANY' : 'ALL',
                    $targetSet->propertyToExpression($constraint->getReferencedProperty())->getStatement(),
                    $targetSet->quoteSingleIdentifier($targetSet->getTable()),
                )
            );

            $operatingTargetSet = clone $targetSet;

            $parser->pushEntitySet($operatingTargetSet);
            $targetExpression = new SQLExpression($operatingTargetSet);
            $targetExpression->evaluate($lambdaExpression);
            $parser->popEntitySet();

            $this->pushStatement($targetExpression->getStatement());
            $this->parameters = array_merge($this->parameters, $targetExpression->getParameters());

            $this->pushStatement(') )');

            if ($constraints) {
                $this->pushStatement('OR ');
            }
        }
    }
}