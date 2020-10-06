<?php

namespace Flat3\OData\PathComponent;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Interfaces\PipeInterface;

class Filter implements PipeInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentComponent);

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
