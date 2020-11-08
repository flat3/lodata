<?php

namespace Flat3\Lodata\Exception\Internal;

use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Lexer;

/**
 * Parser Exception
 * @package Flat3\Lodata\Exception\Internal
 */
final class ParserException extends BadRequestException
{
    public function __construct(string $message, Lexer $lexer = null)
    {
        if ($lexer) {
            $message .= ' at: '.$lexer->errorContext();
        }

        parent::__construct('expression_parser_error', $message);
    }
}
