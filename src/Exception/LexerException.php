<?php

namespace Flat3\OData\Exception;

final class LexerException extends BadRequestException
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
