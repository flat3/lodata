<?php

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\QueryOptions\ExpandInterface;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Transaction\Option;

/**
 * Class Expand
 *
 * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionexpand
 */
class Expand extends Option
{
    public const param = 'expand';
    public const query_interface = ExpandInterface::class;

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

            /** @var NavigationProperty $navigationProperty */
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

            $requests[] = new \Flat3\Lodata\Transaction\Expand($navigationProperty, $options);

            if (!$lexer->finished()) {
                $lexer->char(',');
            }
        }

        return $requests;
    }
}
