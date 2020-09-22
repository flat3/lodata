<?php

namespace Flat3\OData\Option;

use Flat3\OData\EntityType;
use Flat3\OData\Exception\BadRequestException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\ObjectArray;
use Flat3\OData\Option;
use Flat3\OData\Property\Navigation;
use Flat3\OData\Request;

/**
 * Class Expand
 *
 * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionexpand
 */
class Expand extends Option
{
    public const param = 'expand';

    public function getExpansionRequests(EntityType $entityType): ObjectArray
    {
        $expanded = $this->getValue();

        $requests = new ObjectArray();

        if (!$expanded) {
            return $requests;
        }

        $lexer = new Lexer($expanded);

        while (!$lexer->finished()) {
            $path = $lexer->maybeODataIdentifier();

            /** @var Navigation $navigationProperty */
            $navigationProperty = $entityType->getNavigationProperties()->get($path);

            if (null === $navigationProperty) {
                throw new BadRequestException(
                    sprintf(
                        'The requested expand path "%s" does not exist on this entity type',
                        $path
                    )
                );
            }

            if (!$navigationProperty->isExpandable()) {
                throw new BadRequestException(
                    sprintf(
                        'The requested path "%s" is not available for expansion on this entity type',
                        $path
                    )
                );
            }

            $options = $lexer->maybeMatchingParenthesis();

            $requests[] = new Request\Expand($navigationProperty, $options);

            if (!$lexer->finished()) {
                $lexer->char(',');
            }
        }

        return $requests;
    }
}
