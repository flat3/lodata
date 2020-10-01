<?php

namespace Flat3\OData\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\ObjectArray;
use Flat3\OData\Option;
use Flat3\OData\Property\Navigation;
use Flat3\OData\Request;
use Flat3\OData\Type;
use Flat3\OData\Type\EntityType;
use RuntimeException;

/**
 * Class Expand
 *
 * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionexpand
 */
class Expand extends Option
{
    public const param = 'expand';

    public function getExpansionRequests(Type $entityType): ObjectArray
    {
        if (!$entityType instanceof EntityType) {
            throw new RuntimeException('Supplied type was not an entity type');
        }

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
                    'nonexistent_expand_path',
                    sprintf(
                        'The requested expand path "%s" does not exist on this entity type',
                        $path
                    )
                );
            }

            if (!$navigationProperty->isExpandable()) {
                throw new BadRequestException(
                    'path_not_expandable',
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
