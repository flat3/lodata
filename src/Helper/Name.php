<?php

namespace Flat3\OData\Helper;

use Flat3\OData\Exception\ConfigurationException;
use Flat3\OData\Expression\Lexer;

final class Name
{
    /** @var string $name */
    private $name;

    public function __construct(string $name)
    {
        if (!Lexer::patternCheck(Lexer::ODATA_IDENTIFIER, $name)) {
            throw new ConfigurationException('The provided name was invalid', $name);
        }

        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
