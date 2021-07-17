<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression;

use Exception;
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
    public const BASE64 = '([A-Za-z0-9_-]{4})*([A-Za-z0-9_-]{3}[A-Za-z0-9_-]|[A-Za-z0-9_-]{2}[AEIMQUYcgkosw048]=?|[A-Za-z0-9_-][AQgw](==)?)?';
    public const IDENTIFIER = '([A-Za-z_\p{L}\p{Nl}][A-Za-z_0-9\p{L}\p{Nl}\p{Nd}\p{Mn}\p{Mc}\p{Pc}\p{Cf}]{0,127})';
    public const QUALIFIED_IDENTIFIER = '(?:'.self::IDENTIFIER.'\.?)*'.self::IDENTIFIER;
    public const PARAMETER_ALIAS = '\@'.self::IDENTIFIER;
    public const DURATION = '-?P([0-9]+D)?(T([0-9]+H)?([0-9]+M)?([0-9]+([.][0-9]+)?S)?)?';
    public const DATE_TIME_OFFSET = '[0-9]{4,}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]([.][0-9]{1,12})?(Z|[+-][0-9][0-9]:[0-9][0-9])';
    public const DATE = '[0-9]{4,}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])';
    public const GUID = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
    public const TIME_OF_DAY = '([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]([.][0-9]{1,12})?';
    public const DIGIT = '\d';
    public const PATH_SEPARATOR = '/';
    public const LAMBDA_VARIABLE = self::IDENTIFIER.'\:';

    /**
     * The text passed to the Lexer
     * @var string|null
     * @internal
     */
    private $text;

    /**
     * The position of the pointer
     * @var int
     * @internal
     */
    private $pos = -1;

    /**
     * The length of the buffer
     * @var int
     * @internal
     */
    private $len;

    public function __construct(?string $expression)
    {
        $this->text = $expression;
        $this->len = strlen($expression) - 1;
    }

    /**
     * Check the provided pattern against the value
     * @param  string  $expression  Expression
     * @param ?string  $value  Value
     * @return bool
     */
    public static function patternCheck(string $expression, string $value): bool
    {
        return preg_match('@^'.$expression.'$@', $value) === 1;
    }

    /**
     * Match the provided pattern against the value
     * @param  string  $expression  Expression
     * @param  ?string  $value  Value
     * @return array|null
     */
    public static function patternMatch(string $expression, ?string $value): ?array
    {
        $result = preg_match('@^'.$expression.'$@', $value, $matches);

        return $result === 1 ? $matches : null;
    }

    /**
     * Provide the current state of the lexer to report in errors
     * @return string State
     */
    public function errorContext(): string
    {
        $context = 32;
        $error_pos = $this->pos + 1;
        $left_pos = $error_pos - $context;
        $left_pos = $left_pos < 0 ? 0 : $left_pos;
        $right_pos = $left_pos + ($context * 2) + 1;
        $right_pos = $right_pos > $this->len ? $this->len : $right_pos;

        if ($error_pos > $this->len) {
            return sprintf('%s<EOF', substr($this->text, $left_pos, $error_pos - $left_pos));
        }

        return sprintf(
            '%s>%s<%s',
            substr($this->text, $left_pos, $error_pos - $left_pos),
            $this->text[$error_pos],
            substr($this->text, $error_pos + 1, $right_pos - $this->pos)
        );
    }

    /**
     * Match one of the provided rules
     * @param  mixed  ...$rules
     * @return mixed
     * @throws LexerException
     * @throws Exception
     */
    public function match(...$rules)
    {
        $last_error_pos = -1;
        $last_exception = null;
        $last_error_rules = [];

        foreach ($rules as $rule) {
            $initial_pos = $this->pos;

            try {
                $func = $rule;
                $args = [];

                if (is_array($rule)) {
                    $func = array_pop($rule);
                    $args = $rule;
                }

                return $this->$func(...$args);
            } catch (LexerException $e) {
                $this->pos = $initial_pos;

                if ($e->pos > $last_error_pos) {
                    $last_exception = $e;
                    $last_error_pos = $e->pos;
                    $last_error_rules = [$rule];
                } elseif ($e->pos === $last_error_pos) {
                    $last_error_rules[] = $rule;
                }
            }
        }

        if (count($last_error_rules) === 1) {
            throw $last_exception;
        } else {
            throw new LexerException(
                $last_error_pos,
                'Expected %s but got %s',
                implode(',', $last_error_rules),
                $this->text[$last_error_pos]
            );
        }
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

        if (!$result) {
            throw new LexerException($this->pos + 1, 'Unhandled type');
        }

        if (!$this->finished()) {
            throw new LexerException($this->pos + 1, 'Not complete');
        }

        return $result;
    }

    /**
     * Match a base64 value
     * @return string
     * @throws LexerException
     */
    public function base64()
    {
        return $this->expression(self::BASE64);
    }

    /**
     * Maybe match whitespace
     * @return string|null
     */
    public function maybeWhitespace()
    {
        try {
            return $this->whitespace();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Match whitespace
     * @return string
     */
    public function whitespace()
    {
        return $this->expression('\s+');
    }

    /**
     * Parse the provided regular expression
     * @param  string  $pattern  Expression
     * @param  bool  $wrapped  Whether the expression needs to be wrapped in escape characters
     * @return string
     * @throws LexerException
     */
    public function expression(string $pattern, bool $wrapped = false): string
    {
        if ($this->pos >= $this->len) {
            throw new LexerException($this->pos + 1, 'Expected %s but got end of string', $pattern);
        }

        if (!$wrapped) {
            $pattern = '@^'.$pattern.'@';
        }

        $result = preg_match($pattern, substr($this->text, $this->pos + 1), $matches);

        if (false === $result) {
            throw new LexerException($this->pos + 1, 'Invalid expression match response', $pattern);
        }

        if (0 === $result) {
            throw new LexerException($this->pos + 1, 'Expected %s but did not match', $pattern);
        }

        $match = $matches[0];
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
        return $this->keyword(Constants::TRUE, Constants::FALSE);
    }

    /**
     * Match a keyword, case insensitively
     * @param  mixed  ...$keywords
     * @return string
     * @throws LexerException
     */
    public function keyword(...$keywords): string
    {
        if ($this->pos >= $this->len) {
            throw new LexerException($this->pos + 1, 'Expected %s but got end of string', implode(',', $keywords));
        }

        // Ensure the longest keyword is matched first
        self::sortArrayByLength($keywords);

        foreach ($keywords as $keyword) {
            if (strtolower(substr($this->text, $this->pos + 1, strlen($keyword))) === strtolower($keyword)) {
                $this->pos += strlen($keyword);

                return $keyword;
            }
        }

        throw new LexerException(
            $this->pos + 1,
            'Expected %s but got %s',
            implode(',', $keywords),
            $this->text[$this->pos + 1]
        );
    }

    /**
     * Sort the provided array by value length
     * @param  array  $array
     */
    public static function sortArrayByLength(&$array)
    {
        usort($array, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
    }

    /**
     * Match a float
     * @return float|int
     * @throws LexerException
     */
    public function number()
    {
        $chars = [];

        $nan = $this->maybeKeyword(Constants::NOT_A_NUMBER);

        if ($nan) {
            return NAN;
        }

        $sign = $this->maybeKeyword('+', '-');

        $inf = $this->maybeKeyword(Constants::INFINITY);

        if ($inf) {
            return $sign === '-' ? -INF : INF;
        }

        if (null !== $sign) {
            $chars[] = $sign;
        }

        try {
            $chars[] = $this->expression(self::DIGIT);
        } catch (LexerException $e) {
            if ($sign) {
                $this->pos--;
            }

            throw $e;
        }

        while (true) {
            $char = $this->maybeExpression(self::DIGIT);
            if (null === $char) {
                break;
            }

            $chars[] = $char;
        }

        if ($this->maybeChar('.')) {
            $chars[] = '.';
            $chars[] = $this->expression(self::DIGIT);

            while (true) {
                $char = $this->maybeExpression(self::DIGIT);
                if (null === $char) {
                    break;
                }

                $chars[] = $char;
            }
        } else {
            return (int) implode('', $chars);
        }

        return (float) implode('', $chars);
    }

    /**
     * Maybe match a keyword
     * @param  mixed  ...$args
     * @return null|string
     */
    public function maybeKeyword(...$args): ?string
    {
        try {
            return $this->keyword(...$args);
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match an expression
     * @param  mixed  ...$args
     * @return string|null
     */
    public function maybeExpression(...$args): ?string
    {
        try {
            return $this->expression(...$args);
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match from a character list
     * @param  mixed  ...$args
     * @return string|null
     */
    public function maybeChar(...$args): ?string
    {
        try {
            return $this->char(...$args);
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a parameter alias
     * @return string|null
     */
    public function maybeParameterAlias(): ?string
    {
        try {
            return $this->parameterAlias();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a date time offset
     * @return string|null
     */
    public function maybeDateTimeOffset(): ?string
    {
        try {
            return $this->datetimeoffset();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a date
     * @return string|null
     */
    public function maybeDate(): ?string
    {
        try {
            return $this->date();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a time of day
     * @return string|null
     */
    public function maybeTimeOfDay(): ?string
    {
        try {
            return $this->timeOfDay();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Match one of the provided chars
     * @param  string  $char
     * @return string
     * @throws LexerException
     */
    public function char($char = ''): string
    {
        if (strlen($char) > 1) {
            throw new LexerException($this->pos + 1, 'The char() function only accepts zero or one characters');
        }

        if ($this->pos >= $this->len) {
            throw new LexerException(
                $this->pos + 1,
                'Expected %s but got end of string',
                $char ? "'$char'" : 'character'
            );
        }

        $next_char = $this->text[$this->pos + 1];

        if (!$char || $next_char === $char) {
            $this->pos++;

            return $next_char;
        }

        throw new LexerException($this->pos + 1, 'Expected %s but got %s', $char, $next_char);
    }

    /**
     * Match a parameter alias
     * @return string
     * @throws LexerException
     */
    public function parameterAlias()
    {
        return $this->expression(self::PARAMETER_ALIAS);
    }

    /**
     * Match a date time offset
     * @return string
     * @throws LexerException
     */
    public function datetimeoffset()
    {
        return $this->expression(self::DATE_TIME_OFFSET);
    }

    /**
     * Match a date
     * @return string
     * @throws LexerException
     */
    public function date()
    {
        return $this->expression(self::DATE);
    }

    /**
     * Match a time of day
     * @return string
     * @throws LexerException
     */
    public function timeOfDay()
    {
        return $this->expression(self::TIME_OF_DAY);
    }

    /**
     * Match a duration
     * @return string
     * @throws LexerException
     */
    public function duration()
    {
        return $this->expression(self::DURATION);
    }

    /**
     * Match a GUID
     * @return string
     * @throws LexerException
     */
    public function guid(): string
    {
        return $this->expression(self::GUID);
    }

    /**
     * Match a quoted string
     * @param  string  $quoteChar
     * @return string
     * @throws LexerException
     */
    public function quotedString($quoteChar = "'"): string
    {
        $this->char($quoteChar);

        $chars = [];

        while (true) {
            $char = $this->char();

            if ($quoteChar === $char) {
                if ($this->pos + 1 < $this->len && $quoteChar === $this->text[$this->pos + 1]) {
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
        return substr($this->text, $this->pos + 1);
    }

    /**
     * Maybe match a single quoted string
     * @return string|null
     */
    public function maybeSingleQuotedString(): ?string
    {
        try {
            return $this->quotedString();
        } catch (LexerException $e) {
            return null;
        }
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
     * Maybe match a GUID
     * @return string|null
     */
    public function maybeGuid(): ?string
    {
        try {
            return $this->guid();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a duration
     * @return string|null
     */
    public function maybeDuration(): ?string
    {
        try {
            return $this->duration();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a string
     * @return string|null
     */
    public function maybeString(): ?string
    {
        try {
            return $this->string_();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Match a string
     * @return string
     * @throws LexerException
     */
    public function string_(): string
    {
        return $this->expression('[^ \'"\(\)]+');
    }

    /**
     * Maybe match a boolean
     * @return string|null
     */
    public function maybeBoolean(): ?string
    {
        try {
            return $this->boolean();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a number
     * @return float|int|null
     */
    public function maybeNumber()
    {
        try {
            return $this->number();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match an identifier
     * @return string|null
     */
    public function maybeIdentifier(): ?string
    {
        try {
            return $this->identifier();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a qualified identifier
     * @return string|null
     */
    public function maybeQualifiedIdentifier(): ?string
    {
        try {
            return $this->qualifiedIdentifier();
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
        return $this->expression(self::IDENTIFIER);
    }

    /**
     * Match a qualified identifier
     * @return string
     * @throws LexerException
     */
    public function qualifiedIdentifier(): string
    {
        return $this->expression(self::QUALIFIED_IDENTIFIER);
    }

    /**
     * Maybe match a lambda variable
     * @return string|null
     */
    public function maybeLambdaVariable(): ?string
    {
        return $this->maybeExpression(self::LAMBDA_VARIABLE);
    }

    /**
     * Maybe match a string enclosed in matching parentheses
     * @return string|null
     */
    public function maybeMatchingParenthesis(): ?string
    {
        try {
            return $this->matchingParenthesis();
        } catch (LexerException $e) {
            return null;
        }
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
