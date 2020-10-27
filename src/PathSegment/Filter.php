<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Interfaces\PipeInterface;

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

        $filter = $lexer->matchingParenthesis();

        $transaction->getFilter()->setValue($filter);

        return $argument;
    }
}
