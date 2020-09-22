<?php

namespace Flat3\OData\Exception\Internal;

use Flat3\OData\Expression\Lexer;
use RuntimeException;

final class ParserException extends RuntimeException
{
    public function __construct(string $message, Lexer $lexer = null)
    {
        if ($lexer) {
            $message .= ' at: '.$lexer->errorContext();
        }

        parent::__construct($message);
    }
}
