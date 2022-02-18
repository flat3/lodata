<?php

declare(strict_types=1);

namespace Flat3\Lodata\Client;

use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\EnumeratesValues;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\HttpFoundation\Response;
use Traversable;

class Collection implements Enumerable
{
    use EnumeratesValues;
    use Macroable;

    protected $url;
    protected $query = [];
    protected $maxPageSize = 1000;
    protected $limit = null;

    public function __construct(string $source)
    {
        $this->url = $source;
    }

    protected function sendRequest(string $url, array $query = [])
    {
        $headers = [
            'accept' => 'application/json;metadata=full',
            'prefer' => 'maxpagesize='.$this->maxPageSize,
        ];

        return Http::withHeaders($headers)->get($url, $query);
    }

    public function getIterator(): Traversable
    {
        $total = 0;

        $response = $this->sendRequest($this->url, $this->query);

        do {
            $entities = $response->offsetGet('value');

            foreach ($entities as $entity) {
                list ($key, $entity) = $this->convertEntity($entity);
                yield $key => $entity;
                $total++;
            }

            if ($response->offsetExists('@nextLink')) {
                $response = $this->sendRequest($response->offsetGet('@nextLink'));
                continue;
            }

            break;
        } while (!$this->limit || $total < $this->limit);
    }

    protected function convertEntity(array $entity): array
    {
        $key = json_decode((string) Str::of($entity['@id'])->match('/\((.*)\)$/'));

        $entity = array_filter($entity, function ($key) {
            return !Str::startsWith($key, '@');
        }, ARRAY_FILTER_USE_KEY);

        return [$key, $entity];
    }

    protected function lazy(): LazyCollection
    {
        return new LazyCollection(function () {
            return $this->getIterator();
        });
    }

    public static function convertOperator(string $operator): string
    {
        switch ($operator) {
            case '=':
            case '==':
            case '===':
                return 'eq';
            case '!=':
            case '!==':
            case '<>':
                return 'ne';
            case '<':
                return 'lt';
            case '>':
                return 'gt';
            case '<=':
                return 'le';
            case '>=':
                return 'ge';
        }

        throw new \RuntimeException('Unknown operator '.$operator);
    }

    public static function escape($value)
    {
        switch (gettype($value)) {
            case 'string':
                return "'{$value}'";
            default:
                return $value;
        }
    }

    public function get($key, $default = null)
    {
        $response = $this->sendRequest(sprintf('%s/%s', $this->url, $key));

        if ($response->status() === Response::HTTP_NOT_FOUND) {
            return is_callable($default) ? $default() : ($default ?: null);
        }

        list (, $entity) = $this->convertEntity($response->json());

        return $entity;
    }

    public function has($key): bool
    {
        return null !== $this->get($key);
    }

    public function count(): int
    {
        return (int) Http::get($this->url.'/$count', $this->query)->body();
    }

    public function skip($count): Collection
    {
        $this->query['$skip'] = $count;

        return $this;
    }

    public function take($limit): Collection
    {
        $this->limit = $limit;
        $this->query['$top'] = $limit;

        return $this;
    }

    public function top($limit): Collection
    {
        return $this->take($limit);
    }

    public function filter($callback = null): Enumerable
    {
        if (is_string($callback)) {
            $this->query['$filter'] = $callback;
            return $this;
        }

        return $this->lazy()->filter($callback);
    }

    public function search($value, $strict = false): Collection
    {
        $this->query['$search'] = $value;

        return $this;
    }

    public function sortBy($callback, $options = SORT_REGULAR, $descending = false): Enumerable
    {
        if (is_string($callback)) {
            $this->query['$orderby'] = $callback.' '.($descending ? 'desc' : 'asc');

            return $this;
        }

        return $this->lazy()->sortBy($callback, $options, $descending);
    }

    public function forPage($page, $perPage): Collection
    {
        $this->take($perPage);
        $this->skip(($page - 1) * $perPage);

        return $this;
    }

    public function slice($offset, $length = null): Collection
    {
        if ($length) {
            $this->take($length);
        }

        $this->skip($offset);

        return $this;
    }

    public static function range($from, $to)
    {
        return LazyCollection::range($from, $to);
    }

    public function all()
    {
        return $this->lazy()->all();
    }

    public function median($key = null)
    {
        return $this->lazy()->median($key);
    }

    public function mode($key = null)
    {
        return $this->lazy()->mode($key);
    }

    public function collapse()
    {
        return $this->lazy()->collapse();
    }

    public function avg($callback = null)
    {
        return $this->lazy()->avg($callback);
    }

    public function contains($key, $operator = null, $value = null): bool
    {
        $this->query['$filter'] = sprintf('%s %s %s', $key, self::convertOperator($operator), self::escape($value));

        return $this->isNotEmpty();
    }

    public function crossJoin(...$lists)
    {
        return $this->lazy()->crossJoin(...$lists);
    }

    public function diff($items)
    {
        return $this->lazy()->diff($items);
    }

    public function diffUsing($items, callable $callback)
    {
        return $this->lazy()->diffUsing($items, $callback);
    }

    public function diffAssoc($items)
    {
        return $this->lazy()->diffAssoc($items);
    }

    public function diffAssocUsing($items, callable $callback)
    {
        return $this->diffAssocUsing($items, $callback);
    }

    public function diffKeys($items)
    {
        return $this->lazy()->diffKeys($items);
    }

    public function diffKeysUsing($items, callable $callback)
    {
        return $this->lazy()->diffKeysUsing($items, $callback);
    }

    public function duplicates($callback = null, $strict = false)
    {
        return $this->lazy()->duplicates($callback, $strict);
    }

    public function duplicatesStrict($callback = null)
    {
        return $this->lazy()->duplicatesStrict($callback);
    }

    public function except($keys)
    {
        return $this->lazy()->except($keys);
    }

    public function first(callable $callback = null, $default = null)
    {
        return $this->lazy()->first($callback, $default);
    }

    public function flatten($depth = INF)
    {
        return $this->lazy()->flatten($depth);
    }

    public function flip()
    {
        return $this->lazy()->flip();
    }

    public function groupBy($groupBy, $preserveKeys = false)
    {
        return $this->lazy()->groupBy($groupBy, $preserveKeys);
    }

    public function keyBy($keyBy)
    {
        return $this->lazy()->keyBy($keyBy);
    }

    public function implode($value, $glue = null)
    {
        return $this->lazy()->implode($value, $glue);
    }

    public function intersect($items)
    {
        return $this->lazy()->intersect($items);
    }

    public function intersectByKeys($items)
    {
        return $this->lazy()->intersectByKeys($items);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function join($glue, $finalGlue = ''): string
    {
        return $this->lazy()->join($glue, $finalGlue);
    }

    public function keys()
    {
        return $this->lazy()->keys();
    }

    public function last(callable $callback = null, $default = null)
    {
        return $this->lazy()->last($callback, $default);
    }

    public function map(callable $callback)
    {
        return $this->lazy()->map($callback);
    }

    public function mapToDictionary(callable $callback)
    {
        return $this->lazy()->mapToDictionary($callback);
    }

    public function mapWithKeys(callable $callback)
    {
        return $this->lazy()->mapWithKeys($callback);
    }

    public function merge($items)
    {
        return $this->lazy()->merge($items);
    }

    public function mergeRecursive($items)
    {
        return $this->lazy()->mergeRecursive($items);
    }

    public function combine($values)
    {
        return $this->lazy()->combine($values);
    }

    public function union($items)
    {
        return $this->lazy()->union($items);
    }

    public function nth($step, $offset = 0)
    {
        return $this->lazy()->nth($step, $offset);
    }

    public function only($keys)
    {
        return $this->lazy()->only($keys);
    }

    public function concat($source)
    {
        return $this->lazy()->concat($source);
    }

    public function random($number = null)
    {
        $this->lazy()->random($number);
    }

    public function replace($items)
    {
        return $this->lazy()->replace($items);
    }

    public function replaceRecursive($items)
    {
        return $this->replaceRecursive($items);
    }

    public function reverse()
    {
        return $this->lazy()->reverse();
    }

    public function shuffle($seed = null)
    {
        return $this->lazy()->shuffle($seed);
    }

    public function skipUntil($value)
    {
        return $this->lazy()->skipUntil($value);
    }

    public function skipWhile($value)
    {
        return $this->lazy()->skipWhile($value);
    }

    public function split($numberOfGroups)
    {
        return $this->lazy()->split($numberOfGroups);
    }

    public function chunk($size)
    {
        return $this->lazy()->chunk($size);
    }

    public function chunkWhile(callable $callback)
    {
        return $this->lazy()->chunkWhile($callback);
    }

    public function sort($callback = null)
    {
        return $this->lazy()->sort($callback);
    }

    public function sortDesc($options = SORT_REGULAR)
    {
        return $this->lazy()->sortDesc($options);
    }

    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->lazy()->sortByDesc($callback, $options);
    }

    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        return $this->lazy()->sortKeys($options, $descending);
    }

    public function sortKeysDesc($options = SORT_REGULAR)
    {
        return $this->lazy()->sortKeysDesc($options);
    }

    public function takeUntil($value)
    {
        return $this->lazy()->takeUntil($value);
    }

    public function takeWhile($value)
    {
        return $this->lazy()->takeWhile($value);
    }

    public function pluck($value, $key = null)
    {
        return $this->lazy()->pluck($value, $key);
    }

    public function unique($key = null, $strict = false)
    {
        return $this->lazy()->unique($key, $strict);
    }

    public function values()
    {
        return $this->lazy()->values();
    }

    public function pad($size, $value)
    {
        return $this->lazy()->pad($size, $value);
    }

    public function countBy($callback = null)
    {
        return $this->lazy()->countBy($callback);
    }

    public function zip($items)
    {
        return $this->lazy()->zip($items);
    }

    public function doesntContain($key, $operator = null, $value = null)
    {
        return $this->lazy()->doesntContain($key, $operator, $value);
    }

    public function hasAny($key)
    {
        return $this->lazy()->hasAny($key);
    }

    public function containsOneItem()
    {
        return $this->lazy()->containsOneItem();
    }

    public function sliding($size = 2, $step = 1)
    {
        return $this->lazy()->sliding($size, $step);
    }

    public function sole($key = null, $operator = null, $value = null)
    {
        return $this->lazy()->sole($key, $operator, $value);
    }

    public function firstOrFail($key = null, $operator = null, $value = null)
    {
        return $this->lazy()->firstOrFail($key, $operator, $value);
    }

    public function splitIn($numberOfGroups)
    {
        return $this->lazy()->splitIn($numberOfGroups);
    }

    public function sortKeysUsing(callable $callback)
    {
        return $this->lazy()->sortKeysUsing($callback);
    }

    public function undot()
    {
        return $this->lazy()->undot();
    }
}
