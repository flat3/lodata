<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Event\ArgumentSeparator;
use Flat3\Lodata\Expression\Event\EndFunction;
use Flat3\Lodata\Expression\Event\EndGroup;
use Flat3\Lodata\Expression\Event\Literal;
use Flat3\Lodata\Expression\Event\Operator;
use Flat3\Lodata\Expression\Event\Property;
use Flat3\Lodata\Expression\Event\StartFunction;
use Flat3\Lodata\Expression\Event\StartGroup;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Contains;
use Flat3\Lodata\Expression\Node\Func\StringCollection\EndsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\StartsWith;
use Flat3\Lodata\Expression\Node\Literal\Boolean;
use Flat3\Lodata\Expression\Node\Literal\Date;
use Flat3\Lodata\Expression\Node\Literal\DateTimeOffset;
use Flat3\Lodata\Expression\Node\Literal\Duration;
use Flat3\Lodata\Expression\Node\Literal\TimeOfDay;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Add;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\DivBy;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Mod;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Mul;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Sub;
use Flat3\Lodata\Expression\Node\Operator\Comparison;
use Flat3\Lodata\Expression\Node\Operator\Comparison\And_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Or_;
use Flat3\Lodata\Expression\Node\Operator\Logical\Equal;
use Flat3\Lodata\Expression\Node\Operator\Logical\GreaterThan;
use Flat3\Lodata\Expression\Node\Operator\Logical\GreaterThanOrEqual;
use Flat3\Lodata\Expression\Node\Operator\Logical\In;
use Flat3\Lodata\Expression\Node\Operator\Logical\LessThan;
use Flat3\Lodata\Expression\Node\Operator\Logical\LessThanOrEqual;
use Flat3\Lodata\Expression\Node\Operator\Logical\NotEqual;

/**
 * SQL Filter
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLFilter
{
    use MySQLFilter;
    use PostgreSQLFilter;
    use SQLiteFilter;
    use SQLServerFilter;

    /**
     * Generate SQL fragments for filters
     * @param  Event  $event  Filter event
     * @return bool|null
     */
    public function filter(Event $event): ?bool
    {
        switch (true) {
            case $event instanceof ArgumentSeparator:
                $this->addWhere(',');

                return true;

            case $event instanceof EndGroup:
            case $event instanceof EndFunction:
                $this->addWhere(')');

                return true;

            case $event instanceof Property:
                /** @var EntityType $type */
                $type = $this->getType();

                /** @var DeclaredProperty $property */
                $property = $type->getProperty($event->getValue());

                if (!$property->isFilterable()) {
                    throw new BadRequestException(
                        sprintf('The provided property (%s) is not filterable', $property->getName())
                    );
                }

                $column = $this->propertyToField($property);

                $this->addWhere($column);

                return true;

            case $event instanceof Literal:
                $this->addWhere('?');

                $node = $event->getNode();

                switch (true) {
                    case $node instanceof Boolean:
                        $this->addParameter(null === $event->getValue() ? null : (int) $event->getValue()->get());
                        break;

                    case $node instanceof Date:
                        $this->addParameter($node->getValue()->get()->format('Y-m-d 00:00:00'));
                        break;

                    case $node instanceof Duration:
                        $this->addParameter($node->getValue()->get());
                        break;

                    case $node instanceof DateTimeOffset:
                        $this->addParameter($node->getValue()->get()->format('Y-m-d H:i:s'));
                        break;

                    case $node instanceof TimeOfDay:
                        $this->addParameter($node->getValue()->get()->format('H:i:s'));
                        break;

                    default:
                        $this->addParameter($event->getValue()->get());
                        break;
                }

                return true;

            case $event instanceof Operator:
                $operator = $event->getNode();

                $left = $operator->getLeftNode();
                $right = $operator->getRightNode();

                if (
                    !$operator instanceof Comparison
                    && (
                        $left instanceof StartsWith
                        || $left instanceof EndsWith
                        || $left instanceof Contains
                        || $right instanceof StartsWith
                        || $right instanceof EndsWith
                        || $right instanceof Contains
                    )
                ) {
                    if (!($operator instanceof Equal && $right instanceof Boolean && $right->getValue()->get() === true)) {
                        throw new BadRequestException(
                            'This entity set does not support expression operators with startswith, endswith, contains other than x eq true'
                        );
                    }

                    $this->addWhere(')');

                    throw new NodeHandledException();
                }

                switch (true) {
                    case $operator instanceof Add:
                        $this->addWhere('+');

                        return true;

                    case $operator instanceof DivBy:
                        $this->addWhere('/');

                        return true;

                    case $operator instanceof Mod:
                        $this->addWhere('%');

                        return true;

                    case $operator instanceof Mul:
                        $this->addWhere('*');

                        return true;

                    case $operator instanceof Sub:
                        $this->addWhere('-');

                        return true;

                    case $operator instanceof And_:
                        $this->addWhere('AND');

                        return true;

                    case $operator instanceof Not_:
                        $this->addWhere('NOT');

                        return true;

                    case $operator instanceof Or_:
                        $this->addWhere('OR');

                        return true;

                    case $operator instanceof Equal:
                        $this->addWhere('=');

                        return true;

                    case $operator instanceof GreaterThan:
                        $this->addWhere('>');

                        return true;

                    case $operator instanceof GreaterThanOrEqual:
                        $this->addWhere('>=');

                        return true;

                    case $operator instanceof In:
                        $this->addWhere('IN');

                        return true;

                    case $operator instanceof LessThan:
                        $this->addWhere('<');

                        return true;

                    case $operator instanceof LessThanOrEqual:
                        $this->addWhere('<=');

                        return true;

                    case $operator instanceof NotEqual:
                        $this->addWhere('!=');

                        return true;
                }
                break;

            case $event instanceof StartGroup:
                $this->addWhere('(');

                return true;

            case $event instanceof StartFunction:
                $func = $event->getNode();

                switch (true) {
                    case $func instanceof Contains:
                    case $func instanceof EndsWith:
                    case $func instanceof StartsWith:
                        $arguments = $func->getArguments();
                        list($arg1, $arg2) = $arguments;

                        $arg1->compute();
                        $this->addWhere('LIKE');
                        $value = $arg2->getValue();

                        if ($func instanceof StartsWith || $func instanceof Contains) {
                            $value .= '%';
                        }

                        if ($func instanceof EndsWith || $func instanceof Contains) {
                            $value = '%'.$value;
                        }

                        $arg2->setValue($value);
                        $arg2->compute();
                        throw new NodeHandledException();
                }
        }

        switch ($this->getDriver()) {
            case 'mysql':
                return $this->mysqlFilter($event);

            case 'sqlite':
                return $this->sqliteFilter($event);

            case 'pgsql':
                return $this->pgsqlFilter($event);

            case 'sqlsrv':
                return $this->sqlsrvFilter($event);
        }

        return false;
    }
}
