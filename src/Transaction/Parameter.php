<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;

class Parameter
{
    private $value = null;
    private $parameters = [];

    public function parse($text)
    {
        $lexer = new Lexer($text);

        try {
            while (!$lexer->finished()) {
                $key = $lexer->expression('[^;=]+');
                $value = null;

                if ($lexer->maybeChar('=')) {
                    $value = $lexer->maybeDoubleQuotedString();

                    if (!$value) {
                        $value = $lexer->expression('[^;]+');
                    }
                }

                $this->addParameter($key, $value);
                $lexer->maybeWhitespace();
                $lexer->maybeChar(';');
            }
        } catch (LexerException $e) {
            throw new InternalServerErrorException('invalid_parameter', 'An invalid parameter was provided');
        }
    }

    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function addParameter($key, $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function dropParameter(string $key): self
    {
        unset($this->parameters[$key]);
        return $this;
    }

    public function getParameter(string $key): ?string
    {
        return $this->parameters[$key] ?? null;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function __toString()
    {
        return http_build_query($this->parameters, '', ';');
    }
}
