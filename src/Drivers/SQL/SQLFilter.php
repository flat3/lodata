<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Contains;
use Flat3\Lodata\Expression\Node\Func\StringCollection\EndsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\StartsWith;
use Flat3\Lodata\Expression\Node\Group;
use Flat3\Lodata\Expression\Node\Literal;
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
use Flat3\Lodata\Expression\Node\Property;
use Flat3\Lodata\Expression\Operator;

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
     * @param  Node  $node  Node
     * @return bool|null
     */
    public function filter(Node $node): ?bool
    {
        switch (true) {
            case $node instanceof Group\Separator:
                $this->addWhere(',');

                return true;

            case $node instanceof Group\Start:
                $this->addWhere('(');

                return true;

            case $node instanceof Group\End:
                $this->addWhere(')');

                return true;

            case $node instanceof Property:
                /** @var EntityType $type */
                $type = $this->getType();

                /** @var DeclaredProperty $property */
                $property = $type->getProperty($node->getValue());

                if (!$property || !$property->isFilterable()) {
                    throw new BadRequestException(
                        sprintf('The provided property (%s) is not filterable', $property->getName())
                    );
                }

                $column = $this->propertyToField($property);

                $this->addWhere($column);

                return true;

            case $node instanceof Literal:
                $this->addWhere('?');

                switch (true) {
                    case $node instanceof Boolean:
                        $this->addParameter(null === $node->getValue() ? null : (int) $node->getValue()->get());
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
                        $this->addParameter($node->getValue()->get());
                        break;
                }

                return true;

            case $node instanceof Operator:
                $left = $node->getLeftNode();
                $right = $node->getRightNode();

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

                    $this->addWhere(')');

                    throw new NodeHandledException();
                }

                switch (true) {
                    case $node instanceof Add:
                        $this->addWhere('+');

                        return true;

                    case $node instanceof DivBy:
                        $this->addWhere('/');

                        return true;

                    case $node instanceof Mod:
                        $this->addWhere('%');

                        return true;

                    case $node instanceof Mul:
                        $this->addWhere('*');

                        return true;

                    case $node instanceof Sub:
                        $this->addWhere('-');

                        return true;

                    case $node instanceof And_:
                        $this->addWhere('AND');

                        return true;

                    case $node instanceof Not_:
                        $this->addWhere('NOT');

                        return true;

                    case $node instanceof Or_:
                        $this->addWhere('OR');

                        return true;

                    case $node instanceof Equal:
                        $this->addWhere('=');

                        return true;

                    case $node instanceof GreaterThan:
                        $this->addWhere('>');

                        return true;

                    case $node instanceof GreaterThanOrEqual:
                        $this->addWhere('>=');

                        return true;

                    case $node instanceof In:
                        $this->addWhere('IN');

                        return true;

                    case $node instanceof LessThan:
                        $this->addWhere('<');

                        return true;

                    case $node instanceof LessThanOrEqual:
                        $this->addWhere('<=');

                        return true;

                    case $node instanceof NotEqual:
                        $this->addWhere('!=');

                        return true;
                }
                break;
        }

        switch (true) {
            case $node instanceof Contains:
            case $node instanceof EndsWith:
            case $node instanceof StartsWith:
                $arguments = $node->getArguments();
                list($arg1, $arg2) = $arguments;

                $arg1->compute();
                $this->addWhere('LIKE');
                $value = $arg2->getValue();

                if ($node instanceof StartsWith || $node instanceof Contains) {
                    $value .= '%';
                }

                if ($node instanceof EndsWith || $node instanceof Contains) {
                    $value = '%'.$value;
                }

                $arg2->setValue($value);
                $arg2->compute();
                throw new NodeHandledException();
        }

        switch ($this->getDriver()) {
            case 'mysql':
                return $this->mysqlFilter($node);

            case 'sqlite':
                return $this->sqliteFilter($node);

            case 'pgsql':
                return $this->pgsqlFilter($node);

            case 'sqlsrv':
                return $this->sqlsrvFilter($node);
        }

        return false;
    }
}
