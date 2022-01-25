<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Interfaces\PipeInterface;

/**
 * Filter
 * @package Flat3\Lodata\PathSegment
 */
class Filter implements PipeInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentSegment);

        try {
            $lexer->keyword('$filter');
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        if (!$argument) {
            throw new BadRequestException(
                'missing_filter_argument',
                'The $filter segment must have an argument',
            );
        }

        $filter = $lexer->matchingParenthesis();
        $transaction->getFilter()->addExpression($filter);

        return $argument;
    }
}
