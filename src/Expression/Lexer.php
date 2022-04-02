<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression;

use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type;

/**
 * Lexer
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/abnf/odata-abnf-construction-rules.txt
 * @package Flat3\Lodata\Expression
 */
class Lexer
{
    public const base64 = '([A-Za-z0-9_-]{4})*([A-Za-z0-9_-]{3}[A-Za-z0-9_-]|[A-Za-z0-9_-]{2}[AEIMQUYcgkosw048]=?|[A-Za-z0-9_-][AQgw](==)?)?';
    public const identifier = '([A-Za-z_\p{L}\p{Nl}][A-Za-z_0-9\p{L}\p{Nl}\p{Nd}\p{Mn}\p{Mc}\p{Pc}\p{Cf}]{0,127})';
    public const qualifiedIdentifier = '(?:'.self::identifier.'\.?)*'.self::identifier;
    public const parameterAlias = '\@'.self::identifier;
    public const duration = '(-?)P(?=\d|T\d)(\d+Y)?(\d+M)?(\d+[DW])?(T(\d+H)?(\d+M)?((\d+(\.\d+)?)S)?)?';
    public const dateTimeOffset = '[0-9]{4,}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]([.][0-9]{1,12})?(Z|[+-][0-9][0-9]:[0-9][0-9])';
    public const date = '[0-9]{4,}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])';
    public const guid = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
    public const timeOfDay = '([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]([.][0-9]{1,12})?';
    public const digit = '\d';
    public const pathSeparator = '/';
    public const lambdaVariable = self::identifier.'\:';
    public const computeExpression = '(.*?) as '.self::identifier;

    /**
     * The text passed to the Lexer
     * @var string|null
     */
    private $text;

    /**
     * The position of the pointer
     * @var int
     */
    private $pos = 0;

    /**
     * The length of the buffer
     * @var int
     */
    private $len;

    public function __construct(string $expression)
    {
        $this->text = $expression;
        $this->len = strlen($expression);
    }

    /**
     * Wrap the provided callable to reset the lexer if no match is found
     * @param  callable  $callback
     * @return mixed
     */
    public function with(callable $callback)
    {
        $result = null;
        $pos = $this->pos;

        try {
            $result = $callback($this);
        } catch (LexerException $e) {
        }

        if (null === $result) {
            $this->pos = $pos;
        }

        return $result;
    }

    /**
     * Check the provided pattern against the value
     * @param  string  $expression  Expression
     * @param  string  $value  Value
     * @return bool
     */
    public static function patternCheck(string $expression, string $value): bool
    {
        return preg_match('@^'.$expression.'$@', $value) === 1;
    }

    /**
     * Provide the current state of the lexer to report in errors
     * @return string State
     */
    public function errorContext(): string
    {
        $context = 32;
        $error_pos = $this->pos;
        $left_pos = $error_pos - $context;
        $left_pos = max($left_pos, 0);
        $right_pos = $left_pos + ($context * 2);
        $right_pos = min($right_pos, $this->len);

        if ($error_pos >= $this->len) {
            return sprintf('%s<END', substr($this->text, $left_pos, $error_pos - $left_pos));
        }

        return sprintf(
            '%s>%s<%s',
            substr($this->text, $left_pos, $error_pos - $left_pos),
            $this->text[$error_pos],
            substr($this->text, $error_pos + 1, $right_pos - $this->pos)
        );
    }

    /**
     * Match the provided type and return a primitive of that type
     * @param  Type  $type  Type
     * @return Primitive Primitive
     * @throws LexerException
     */
    public function type(Type $type): Primitive
    {
        /** @var Primitive $factory */
        $factory = $type->getFactory();
        $result = $factory::fromLexer($this);

        if (!$this->finished()) {
            throw new LexerException($this->pos, 'Not complete');
        }

        return $result;
    }

    /**
     * Match a base64 value
     * @return string
     * @throws LexerException
     */
    public function base64(): string
    {
        return $this->expression(self::base64);
    }

    /**
     * Maybe match whitespace
     * @return string|null
     */
    public function maybeWhitespace(): ?string
    {
        return $this->with(function () {
            return $this->whitespace();
        });
    }

    /**
     * Match whitespace
     * @return string
     */
    public function whitespace(): string
    {
        return $this->expression('\s+');
    }

    /**
     * Parse the provided regular expression
     * @param  string  $pattern  Expression
     * @param  bool  $caseSensitive  Match case sensitively
     * @param  int  $matching  The expression section to return
     * @return string
     */
    public function expression(string $pattern, bool $caseSensitive = true, int $matching = 0): string
    {
        if ($this->pos > $this->len) {
            throw new LexerException($this->pos, 'Expected %s but got end of string', $pattern);
        }

        $pattern = '@^'.$pattern.'@';

        if (!$caseSensitive) {
            $pattern .= 'i';
        }

        $result = preg_match($pattern, substr($this->text, $this->pos), $matches);

        if (false === $result) {
            throw new LexerException($this->pos, 'Invalid expression match response', $pattern);
        }

        if (0 === $result) {
            throw new LexerException($this->pos, 'Expected %s but did not match', $pattern);
        }

        $match = $matches[$matching];
        $this->pos += strlen($match);

        return $match;
    }

    /**
     * Match a boolean
     * @return string
     * @throws LexerException
     */
    public function boolean(): string
    {
        return $this->literal(Constants::true, Constants::false);
    }

    /**
     * Match a keyword
     * @param  mixed  ...$keywords
     * @return string
     * @throws LexerException
     */
    public function literal(...$keywords): string
    {
        if ($this->pos > $this->len) {
            throw new LexerException($this->pos, 'Expected %s but got end of string', implode(',', $keywords));
        }

        foreach ($keywords as $keyword) {
            if (substr($this->text, $this->pos, strlen($keyword)) === $keyword) {
                $this->pos += strlen($keyword);

                return $keyword;
            }
        }

        throw new LexerException(
            $this->pos,
            'Expected %s but got %s',
            implode(',', $keywords),
            $this->text[$this->pos]
        );
    }

    /**
     * Match a float
     * @return float|int
     * @throws LexerException
     */
    public function number()
    {
        return $this->with(function () {
            $chars = [];

            $nan = $this->maybeLiteral(Constants::notANumber);

            if ($nan) {
                return NAN;
            }

            $sign = $this->maybeLiteral('+', '-');

            $inf = $this->maybeLiteral(Constants::infinity);

            if ($inf) {
                return $sign === '-' ? -INF : INF;
            }

            if (null !== $sign) {
                $chars[] = $sign;
            }

            $chars[] = $this->expression(self::digit);

            while (true) {
                $char = $this->maybeExpression(self::digit);
                if (null === $char) {
                    break;
                }

                $chars[] = $char;
            }

            if ($this->maybeChar('.')) {
                $chars[] = '.';
                $chars[] = $this->expression(self::digit);

                while (true) {
                    $char = $this->maybeExpression(self::digit);
                    if (null === $char) {
                        break;
                    }

                    $chars[] = $char;
                }
            } else {
                return (int) implode('', $chars);
            }

            return (float) implode('', $chars);
        });
    }

    /**
     * Maybe match a keyword
     * @param  mixed  ...$args
     * @return null|string
     */
    public function maybeLiteral(...$args): ?string
    {
        return $this->with(function () use ($args) {
            return $this->literal(...$args);
        });
    }

    /**
     * Maybe match an expression
     * @param  mixed  ...$args
     * @return string|null
     */
    public function maybeExpression(...$args): ?string
    {
        return $this->with(function () use ($args) {
            return $this->expression(...$args);
        });
    }

    /**
     * Maybe match from a character list
     * @param  mixed  ...$args
     * @return string|null
     */
    public function maybeChar(...$args): ?string
    {
        return $this->with(function () use ($args) {
            return $this->char(...$args);
        });
    }

    /**
     * Match one of the provided chars
     * @param  string  $char
     * @return string
     * @throws LexerException
     */
    public function char(string $char = ''): string
    {
        if (strlen($char) > 1) {
            throw new LexerException($this->pos, 'The char() function only accepts zero or one characters');
        }

        if ($this->pos >= $this->len) {
            throw new LexerException(
                $this->pos,
                'Expected %s but got end of string',
                $char ? "'$char'" : 'character'
            );
        }

        $next_char = $this->text[$this->pos];

        if (!$char || $next_char === $char) {
            $this->pos++;

            return $next_char;
        }

        throw new LexerException($this->pos, 'Expected %s but got %s', $char, $next_char);
    }

    /**
     * Match a parameter alias
     * @return string
     * @throws LexerException
     */
    public function parameterAlias(): string
    {
        return $this->expression(self::parameterAlias);
    }

    /**
     * Match a date time offset
     * @return string
     * @throws LexerException
     */
    public function datetimeoffset(): string
    {
        return $this->expression(self::dateTimeOffset);
    }

    /**
     * Match a date
     * @return string
     * @throws LexerException
     */
    public function date(): string
    {
        return $this->expression(self::date);
    }

    /**
     * Match a time of day
     * @return string
     * @throws LexerException
     */
    public function timeOfDay(): string
    {
        return $this->expression(self::timeOfDay);
    }

    /**
     * Match a duration
     * @return string
     * @throws LexerException
     */
    public function duration(): string
    {
        return $this->expression(self::duration);
    }

    /**
     * Match a GUID
     * @return string
     * @throws LexerException
     */
    public function guid(): string
    {
        return $this->expression(self::guid);
    }

    /**
     * Match a quoted string
     * @param  string  $quoteChar
     * @return string
     * @throws LexerException
     */
    public function quotedString(string $quoteChar = "'"): string
    {
        $this->char($quoteChar);

        $chars = [];

        while (true) {
            $char = $this->char();

            if ($quoteChar === $char) {
                if ($this->pos < $this->len && $quoteChar === $this->text[$this->pos]) {
                    $this->pos++;
                    $chars[] = $quoteChar;
                    continue;
                } else {
                    break;
                }
            }

            $chars[] = $char;
        }

        return implode('', $chars);
    }

    /**
     * Return whether the lexer is at the end of the string
     * @return bool
     */
    public function finished(): bool
    {
        return $this->pos === $this->len;
    }

    /**
     * Return the remaining text in the buffer
     * @return string
     */
    public function remaining(): string
    {
        return substr($this->text, $this->pos);
    }

    /**
     * Maybe match a double quoted string
     * @return string|null
     */
    public function maybeDoubleQuotedString(): ?string
    {
        try {
            return $this->quotedString('"');
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Match an identifier
     * @return string
     * @throws LexerException
     */
    public function identifier(): string
    {
        return $this->expression(self::identifier);
    }

    /**
     * Match a qualified identifier
     * @return string
     * @throws LexerException
     */
    public function qualifiedIdentifier(): string
    {
        return $this->expression(self::qualifiedIdentifier);
    }

    /**
     * Match a $compute expression
     * @return string|null
     */
    public function computeExpression(): ?string
    {
        return $this->expression(self::computeExpression, true, 1);
    }

    /**
     * Match a function
     * @param  string  $symbol
     * @return string|null
     */
    public function func(string $symbol): ?string
    {
        return $this->with(function () use ($symbol) {
            $result = $this->expression($symbol.'\(', false);

            if ($result) {
                $this->pos--;
                return trim($result, '(');
            }

            return null;
        });
    }

    /**
     * Match a unary operator
     * @param  string  $symbol
     * @return string|null
     */
    public function unaryOperator(string $symbol): ?string
    {
        return $this->with(function () use ($symbol) {
            return trim($this->expression($symbol.'\s', false), ' ');
        });
    }

    /**
     * Match an operator
     * @param  string  $symbol
     * @return string|null
     */
    public function operator(string $symbol): ?string
    {
        return $this->with(function () use ($symbol) {
            return trim($this->expression('\s'.$symbol.'\s', false), ' ');
        });
    }

    /**
     * Match a string enclosed in matching parentheses
     * @return string
     * @throws LexerException
     */
    public function matchingParenthesis(): string
    {
        $this->char('(');
        $chars = [];
        $nesting = 0;

        while (true) {
            $char = $this->char();

            if ($char === '(') {
                $nesting++;
            }

            if ($char === ')') {
                if ($nesting === 0) {
                    break;
                }

                $nesting--;
            }

            $chars[] = $char;
        }

        return implode('', $chars);
    }

    /**
     * Split a semicolon separated query string
     * @return array
     * @throws LexerException
     */
    public function splitSemicolonSeparatedQueryString(): array
    {
        $parameters = [];
        $chars = [];

        while (!$this->finished()) {
            $char = $this->char();

            switch ($char) {
                case '(':
                    $this->pos--;
                    $chars[] = '(';
                    $chars[] = $this->matchingParenthesis();
                    $chars[] = ')';
                    break;

                case ';':
                    $parameters[] = implode('', $chars);
                    $chars = [];
                    break;

                default:
                    $chars[] = $char;
                    break;
            }
        }

        $parameters[] = implode('', $chars);

        return array_reduce($parameters, function ($acc, $parameter) {
            parse_str($parameter, $result);
            return array_merge($acc, $result);
        }, []);
    }
}
