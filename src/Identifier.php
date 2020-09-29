<?php

namespace Flat3\OData;

use Flat3\OData\Exception\ConfigurationException;
use Flat3\OData\Expression\Lexer;

final class Identifier
{
    /** @var string $identifier */
    private $identifier;

    public function __construct(string $identifier)
    {
        if (!Lexer::patternCheck(Lexer::ODATA_IDENTIFIER, $identifier)) {
            throw new ConfigurationException('The provided identifier was invalid', $identifier);
        }

        $this->identifier = $identifier;
    }

    public function get(): string
    {
        return $this->identifier;
    }

    public function __toString(): string
    {
        return $this->identifier;
    }
}
