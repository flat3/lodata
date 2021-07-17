<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Internal;

use RuntimeException;

/**
 * Lexer Exception
 * @package Flat3\Lodata\Exception\Internal
 */
final class LexerException extends RuntimeException
{
    public $pos;
    public $msg;
    public $args;

    public function __construct($pos, $msg, ...$args)
    {
        parent::__construct(sprintf('%s at position %s', sprintf($msg, ...$args), $pos));
        $this->pos = $pos;
        $this->msg = $msg;
        $this->args = $args;
    }
}
