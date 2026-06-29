<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\PipeInterface;

/**
 * Key-As-Segment
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_KeyasSegmentConvention
 * @package Flat3\Lodata\PathSegment
 */
class Key implements PipeInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        // A navigation property that resolves to a collection arrives here wrapped in a
        // PropertyValue (e.g. People('id')/Trips/0). Unwrap it so a following key segment
        // selects a single related entity, mirroring the People('id')/Trips(0) form.
        if ($argument instanceof PropertyValue && $argument->getValue() instanceof EntitySet) {
            $argument = $argument->getValue();
        }

        if (!$argument instanceof EntitySet) {
            throw new PathNotHandledException();
        }

        $keyProperty = $argument->getType()->getKey();

        if (!$keyProperty) {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof ReadInterface) {
            throw new NotImplementedException('entity_cannot_read', 'This entity set cannot read');
        }

        $keyValue = new PropertyValue();
        $keyValue->setProperty($keyProperty);
        $keyValue->setValue($keyProperty->getType()->instance($currentSegment));

        if (!$keyValue->getPrimitive()->matches($currentSegment)) {
            throw new PathNotHandledException();
        }

        $argument->setApplyQueryOptions(true);

        return $argument->negotiateUpsert($keyValue, $transaction, $nextSegment);
    }
}
