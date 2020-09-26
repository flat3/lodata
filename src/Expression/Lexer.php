<?php

namespace Flat3\OData\Expression;

use Exception;
use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Type;

/**
 * Class Lexer
 */
class Lexer
{
    public const OPEN_PAREN = "(?:\(|%28)";
    public const PATH_SEPARATOR = '/';
    public const ODATA_IDENTIFIER = '([A-Za-z_\p{L}\p{Nl}][A-Za-z_0-9\p{L}\p{Nl}\p{Nd}\p{Mn}\p{Mc}\p{Pc}\p{Cf}]{0,127})';
    public const ISO8601 = '([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?';
    public const ISO8601_DURATION = 'P(?:(?:(?P<d>[0-9]+)D)?)?(?:T(?:(?P<h>[0-9]+)H)?(?:(?P<m>[0-9]+)M)?(?:(?P<s>[0-9\.]+)S)?)?';
    public const TIMEOFDAY = '([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9](\.[0-9]{1,12})?)?';
    public const DATE = '([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][--9]|3[0-1])';
    public const GUID = '[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}';
    public const CLOSE_PAREN = "(?:\)|%29)";
    public const DIGIT = '\d';
    public const BASE64 = '(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?';


    private $text;
    private $pos = -1;
    private $len;

    public function __construct($expression)
    {
        $this->text = $expression;
        $this->len = strlen($expression) - 1;
    }

    public static function pattern_check($expression, $value): bool
    {
        return preg_match('@^' . $expression . '$@', $value) === 1;
    }

    public static function patternMatch($expression, $value): ?array
    {
        $result = preg_match('@^' . $expression . '$@', $value, $matches);

        return $result === 1 ? $matches : null;
    }

    /**
     * Provide the current state of the lexer to report in errors
     *
     * @return string
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
     *
     * @param mixed ...$rules
     *
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

    public function type(Type $type): Type
    {
        $result = null;

        switch (true) {
            case $type instanceof Type\Binary:
                $result = $type->factory($this->base64());
                break;

            case $type instanceof Type\Boolean:
                $result = $type->factory($this->boolean());
                break;

            case $type instanceof Type\Byte:
            case $type instanceof Type\Decimal:
                $result = $type->factory($this->number());
                break;

            case $type instanceof Type\Date:
                $result = $type->factory($this->date());
                break;

            case $type instanceof Type\TimeOfDay:
                $result = $type->factory($this->timeOfDay());
                break;

            case $type instanceof Type\DateTimeOffset:
                $result = $type->factory($this->datetimeoffset());
                break;

            case $type instanceof Type\Duration:
                $result = $type->factory($this->duration());
                break;

            case $type instanceof Type\Guid:
                $result = $type->factory($this->guid());
                break;

            case $type instanceof Type\String_:
                $result = $type->factory($this->quotedString());
                break;
        }

        if (!$result) {
            throw new LexerException($this->pos + 1, 'Unhandled type');
        }

        if (!$this->finished()) {
            throw new LexerException($this->pos + 1, 'Not complete');
        }

        return $result;
    }

    public function base64()
    {
        return $this->expression(self::BASE64);
    }

    public function expression(string $pattern, bool $wrapped = false): string
    {
        if ($this->pos >= $this->len) {
            throw new LexerException($this->pos + 1, 'Expected %s but got end of string', $pattern);
        }

        if (!$wrapped) {
            $pattern = '@^' . $pattern . '@';
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
     *
     * @return string
     * @throws LexerException
     */
    public function boolean(): string
    {
        return $this->keyword('true', 'false');
    }

    /**
     * Match a keyword
     *
     * @param mixed ...$keywords
     *
     * @return mixed
     * @throws LexerException
     */
    public function keyword(...$keywords): string
    {
        if ($this->pos >= $this->len) {
            throw new LexerException($this->pos + 1, 'Expected %s but got end of string', implode(',', $keywords));
        }

        // Ensure the longest keyword is matched first
        self::sort_array_by_length($keywords);

        foreach ($keywords as $keyword) {
            if (substr($this->text, $this->pos + 1, strlen($keyword)) === $keyword) {
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

    public static function sort_array_by_length(&$array)
    {
        usort($array, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
    }

    /**
     * Match a float
     *
     * @return float
     * @throws LexerException
     */
    public function number(): float
    {
        $chars = [];
        $sign = $this->maybeKeyword('+', '-');

        if (null !== $sign) {
            $chars[] = $sign;
        }

        $chars[] = $this->expression(self::DIGIT);

        while (true) {
            $char = $this->maybe_expression(self::DIGIT);
            if (null === $char) {
                break;
            }

            $chars[] = $char;
        }

        if ($this->maybeChar('.')) {
            $chars[] = '.';
            $chars[] = $this->expression(self::DIGIT);

            while (true) {
                $char = $this->maybe_expression(self::DIGIT);
                if (null === $char) {
                    break;
                }

                $chars[] = $char;
            }
        }

        return (float)implode('', $chars);
    }

    /**
     * Maybe match a keyword
     *
     * @param mixed ...$args
     *
     * @return mixed|null
     */
    public function maybeKeyword(...$args): ?string
    {
        try {
            return $this->keyword(...$args);
        } catch (LexerException $e) {
            return null;
        }
    }

    public function maybe_expression(...$args): ?string
    {
        try {
            return $this->expression(...$args);
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match from a character list
     *
     * @param mixed ...$args
     *
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

    public function maybeDateTimeOffset(): ?string
    {
        try {
            return $this->datetimeoffset();
        } catch (LexerException $e) {
            return null;
        }
    }

    public function maybeDate(): ?string
    {
        try {
            return $this->date();
        } catch (LexerException $e) {
            return null;
        }
    }

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
     *
     * @param string $char
     *
     * @return string
     * @throws LexerException
     */
    public function char($char = ''): string
    {
        if (strlen($char) > 1) {
            throw new LexerException($this->pos + 1, 'The char() function only accepts zero or one characters');
        }

        if ($this->pos >= $this->len) {
            throw new LexerException($this->pos + 1, 'Expected %s but got end of string', $char);
        }

        $next_char = $this->text[$this->pos + 1];

        if (!$char || $next_char === $char) {
            $this->pos++;

            return $next_char;
        }

        throw new LexerException($this->pos + 1, 'Expected %s but got %s', $char, $next_char);
    }

    public function datetimeoffset()
    {
        return $this->expression(self::ISO8601);
    }

    public function date()
    {
        return $this->expression(self::DATE);
    }

    public function timeOfDay()
    {
        return $this->expression(self::TIMEOFDAY);
    }

    public function duration()
    {
        return $this->expression(self::ISO8601_DURATION);
    }

    public function guid(): string
    {
        return $this->expression(self::GUID);
    }

    /**
     * Match a quoted string
     *
     * @return string|null
     * @throws LexerException
     */
    public function quotedString(): string
    {
        $this->char("'");

        $chars = [];

        while (true) {
            $char = $this->char();

            if ("'" === $char) {
                if ($this->pos + 1 < $this->len && "'" === $this->text[$this->pos + 1]) {
                    $this->pos++;
                    $chars[] = "'";
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
     *
     * @return bool
     */
    public function finished(): bool
    {
        return $this->pos === $this->len;
    }

    /**
     * Maybe match a quoted string
     *
     * @return string|null
     */
    public function maybeQuotedString(): ?string
    {
        try {
            return $this->quotedString();
        } catch (LexerException $e) {
            return null;
        }
    }

    public function maybeGuid(): ?string
    {
        try {
            return $this->guid();
        } catch (LexerException $e) {
            return null;
        }
    }

    /**
     * Maybe match a string
     *
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
     *
     * @return string
     * @throws LexerException
     */
    public function string_(): string
    {
        return $this->expression('[^ \'"\(\)]+');
    }

    /**
     * Maybe match a boolean
     *
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
     *
     * @return float|null
     */
    public function maybeNumber(): ?float
    {
        try {
            return $this->number();
        } catch (LexerException $e) {
            return null;
        }
    }

    public function maybeODataIdentifier(): ?string
    {
        try {
            return $this->odataIdentifier();
        } catch (LexerException $e) {
            return null;
        }
    }

    public function odataIdentifier(): string
    {
        return $this->expression(self::ODATA_IDENTIFIER);
    }

    public function maybeMatchingParenthesis(): ?string
    {
        try {
            return $this->matching_parenthesis();
        } catch (LexerException $e) {
            return null;
        }
    }

    public function matching_parenthesis(): string
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
}
