<?php

namespace Flat3\OData\Exception;

use Flat3\OData\Expression\Lexer;

class BadRequestLexerException extends BadRequestException
{
    public function __construct(string $message, Lexer $lexer)
    {
        parent::__construct(sprintf("%s at '%s'", $message, $lexer->errorContext()));
    }
}
