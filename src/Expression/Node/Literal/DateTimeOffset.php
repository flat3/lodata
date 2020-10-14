<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use DateTime;
use Flat3\Lodata\Expression\Node\Literal;

class DateTimeOffset extends Literal
{
    public function getValue(): DateTime
    {
        return \Flat3\Lodata\Type\DateTimeOffset::factory($this->value)->get();
    }
}
