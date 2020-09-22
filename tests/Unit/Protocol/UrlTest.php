<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Controller\Singular;
use Flat3\OData\Exception\LexerException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\String_;

class UrlTest extends TestCase
{
    public $tests = [
        "/t2('O''Neil')" => "O'Neil",
        '/t2(%27O%27%27Neil%27)' => "O'Neil",
        '/t2%28%27O%27%27Neil%27%29' => "O'Neil",
        '/t2(\'Smartphone%2FTablet\')' => 'Smartphone/Tablet',
    ];

    public function test_valid_urls()
    {
        foreach ($this->tests as $path => $parsed) {
            $pathComponents = Lexer::patternMatch(Singular::path, $path);
            $lexer = new Lexer(rawurldecode(array_pop($pathComponents)));
            $value = $lexer->type(String_::type());
            $this->assertEquals($parsed, $value->getInternalValue());
        }
    }

    public function test_invalid_urls_1()
    {
        $this->expectException(LexerException::class);
        $pathComponents = Lexer::patternMatch(Singular::path, "/t2('O'Neil')");
        $lexer = new Lexer(rawurldecode(array_pop($pathComponents)));
        $lexer->type(String_::type());
    }

    public function test_invalid_urls_2()
    {
        $this->expectException(LexerException::class);
        $pathComponents = Lexer::patternMatch(Singular::path, "/t2('O%27Neil')");
        $lexer = new Lexer(rawurldecode(array_pop($pathComponents)));
        $lexer->type(String_::type());
    }

    public function test_invalid_urls_3()
    {
        $this->expectException(LexerException::class);
        $pathComponents = Lexer::patternMatch(Singular::path, "/t2(\'Smartphone/Tablet\')");
        $lexer = new Lexer(rawurldecode(array_pop($pathComponents)));
        $lexer->type(String_::type());
    }
}
