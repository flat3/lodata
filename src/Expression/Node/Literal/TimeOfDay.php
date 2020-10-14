<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use DateTime;
use Flat3\Lodata\Expression\Node\Literal;

class TimeOfDay extends Literal
{
    public function getValue(): DateTime
    {
        return \Flat3\Lodata\Type\TimeOfDay::factory($this->value)->get();
    }
}
