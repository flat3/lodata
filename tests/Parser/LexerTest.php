<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Parser;

use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Tests\TestCase;

class LexerTest extends TestCase
{
    static public function durations()
    {
        return [
            ['P1Y'],
            ['P2MT30M'],
            ['PT6H'],
            ['P5W'],
            ['PT3S'],
            ['PT3.5S'],
            ['PT1M4S'],
            ['P4DT6H4M45.121999999974S'],
            ['P2856881DT13H35M45S'],
        ];
    }

    static public function bad_durations()
    {
        return [
            ['P'],
            ['PT'],
        ];
    }

    /**
     * @dataProvider durations
     */
    public function test_duration($duration)
    {
        $this->expectNotToPerformAssertions();
        $lexer = new Lexer($duration);
        $lexer->duration();
    }

    /**
     * @dataProvider bad_durations
     */
    public function test_bad_duration($duration)
    {
        $this->expectException(LexerException::class);
        $lexer = new Lexer($duration);
        $lexer->duration();
    }
}
