<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Selfiens\Array2;

use ArrayObject;
use Exception;

/**
 * IMMUTABLE ARRAY helper class.
 * - ArrayObject wrapper with additional methods and *IMMUTABILITY*.
 * - Immutable: this class does not mutate the original array.
 * @template TKey of array-key
 * @template TVal of mixed
 * @extends ArrayObject<TKey,TVal>
 */
class Array2 extends ArrayObject
{

    /**
     * @param array<TKey,TVal> $arr
     */
    public function __construct(array $arr)
    {
        parent::__construct($arr);
    }

    /**
     * @return array<TKey,TVal>
     */
    public function get(): array
    {
        // @phpstan-ignore return.type
        return (array)$this;
    }

    /**
     * Laravel-like
     * @return array<TKey,TVal>
     */
    public function all(): array
    {
        return $this->get();
    }

    /**
     * array values as a list
     * @return list<TVal>
     */
    public function getValues(): array
    {
        return array_values($this->get());
    }

    /**
     * array leaf node values as a list
     * @return list<int|string|float|null>
     */
    public function getValuesFlat(): array
    {
        /** @var list<int|string|float|null> $flat_array */
        $flat_array = $this->valuesFlat()->get();

        return $flat_array;
    }

    /**
     * Pass the underlying array as a whole through $fn()
     * Note: map for each item, pass() is for the whole array
     *
     * @template TKey2 of array-key
     * @template TVal2 of mixed
     *
     * @param callable(array<TKey,TVal>):array<TKey2,TVal2> $fn
     *
     * @return Array2<TKey2,TVal2>
     */
    public function pass(callable $fn): Array2
    {
        return new self($fn($this->get()));
    }

    /**
     * array_filter()
     *
     * @param null|callable(TVal):bool $fn if omitted, 0 are dropped as array_filter() does
     *
     * @return Array2<TKey,TVal>
     */
    public function filter(?callable $fn = null): Array2
    {
        if ($fn === null) {
            $arr = array_filter($this->get());
        } else {
            $arr = array_filter($this->get(), $fn);
        }

        return new self($arr);
    }

    /**
     * A syntactic sugar method that negates the callable result
     *
     * @param callable(TVal):mixed $fn
     *
     * @return Array2<TKey,TVal>
     */
    public function filterNot(callable $fn): Array2
    {
        return (new self($this->get()))->filter(static fn($item) => !$fn($item));
    }

    /**
     * @param callable(TKey):(bool) $fn
     *
     * @return Array2<TKey,TVal>
     */
    public function filterKeys(callable $fn): Array2
    {
        $arr = array_filter($this->get(), fn($key) => $fn($key), ARRAY_FILTER_USE_KEY);

        return new self($arr);
    }

    /**
     * Filter both key and value
     * @param callable(TKey,TVal):(bool) $fn fn(key, value)
     *
     * @return Array2<TKey,TVal>
     */
    public function filterKeyValue(callable $fn): Array2
    {
        $arr = array_filter(
            $this->get(),
            fn($value, $key) => $fn($key, $value),
            ARRAY_FILTER_USE_BOTH,
        );

        return new self($arr);
    }

    public function max(): mixed
    {
        $arr = $this->get();

        return $arr ? max($arr) : null;
    }

    public function min(): mixed
    {
        $arr = $this->get();

        return $arr ? min($arr) : null;
    }

    /**
     * Transform each value through $fn()
     * note: see pass() for the whole array transformation
     * @template TOut of mixed
     *
     * @param callable(TVal):TOut $fn
     *
     * @return Array2<TKey,TOut>
     */
    public function map(callable $fn): Array2
    {
        return new self(array_map($fn, $this->get()));
    }

    public function implode(string $delimiter): string
    {
        return implode($delimiter, $this->get());
    }

    public function join(string $delimiter): string
    {
        return $this->implode($delimiter);
    }

    /**
     * @template TAcc
     * @param callable(TAcc, TVal): TAcc $fn fn(carry, item)
     * @param TAcc                       $init
     * @return TAcc
     */
    public function reduce(callable $fn, mixed $init = null)
    {
        return array_reduce($this->get(), $fn, $init);
    }

    /**
     * Map key and value at the same time.
     *
     * The mapper function fn($key, $value) must return [ mapped_key, mapped_value ].
     * If a mapped_key is null|false, that mapped_key is ignored and the mapped_value is appended to the internal array.
     *
     * @param callable(TKey, TVal):array{mixed,mixed} $fn
     *
     * @return Array2<array-key,mixed>
     * @throws Exception
     */
    public function mapKeyValue(callable $fn): Array2
    {
        $result = self::mapKeyValueImpl($this->get(), $fn);

        return new self($result);
    }

    /**
     * Internal key-value mapper
     *
     * @param iterable<TKey, TVal>                    $iterable
     * @param callable(TKey, TVal):array{mixed,mixed} $mapper must return [ mapped_key, mapped_value ]
     *                                                        When mapped_key is null|false, the mapped_value is pushed to the array.
     *
     * @return array<mixed>
     * @throws Exception
     */
    private static function mapKeyValueImpl(iterable $iterable, callable $mapper): array
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            $temp = $mapper($key, $value);
            // @phpstan-ignore function.alreadyNarrowedType (runtime check)
            if (!is_array($temp)) {
                throw new Exception(__METHOD__ . ' mapper MUST return key,value as a [key,value] array');
            }
            // I deliberately choose not to support [k=>v] shape return: it cannot express auto-indexed key(=NULL)
            // @phpstan-ignore notEqual.alwaysFalse (runtime check)
            if (count($temp) != 2) {
                throw new Exception(__METHOD__ . ' mapper MUST return key,value as a [key,value] array');
            }

            // 이하 검사 완료
            [$mapped_key, $mapped_value] = $temp;

            // falsy 한 key는 무시하고 push 처리
            if ($mapped_key === null || $mapped_key === false) {
                $result[] = $mapped_value;
            } else {
                $result[$mapped_key] = $mapped_value;
            }
        }

        return $result;
    }

    /**
     * @return Array2<int,TKey>
     */
    public function keys(): Array2
    {
        return new self(array_keys($this->get()));
    }

    /**
     * @return Array2<int,mixed>
     */
    public function values(): Array2
    {
        return new self(array_values($this->get()));
    }

    /**
     * @return Array2<int,TVal>
     */
    public function valuesFlat(): Array2
    {
        return new self(self::valuesFlatImpl($this->get()));
    }

    /**
     * Flatten multi-dimensional array and return leaf node values as a list
     *
     * @param array<TVal|array<TVal>> $array
     *
     * @return list<TVal> list<TVal of leaf-node>
     */
    private static function valuesFlatImpl(array $array): array
    {
        $deflated = [];
        foreach ($array as $v1) {
            if (is_array($v1)) {
                foreach (self::valuesFlatImpl($v1) as $v2) {
                    $deflated[] = $v2;
                }
            } else {
                $deflated[] = $v1;
            }
        }

        return $deflated;
    }

    /**
     * @param mixed $val
     *
     * @return Array2<TKey,TVal>
     */
    public function valuesEq($val, bool $is_strict = false): Array2
    {
        return (new self($this->get()))
            ->filter(static fn($item) => ($is_strict) ? $item === $val : $item == $val);
    }

    /**
     * @param mixed $val
     *
     * @return Array2<TKey,TVal>
     */
    public function valuesNotEq($val, bool $is_strict = false): Array2
    {
        return (new self($this->get()))
            ->filter(static fn($item) => ($is_strict) ? $item !== $val : $item != $val);
    }

    public function average(): float|int
    {
        $arr = $this->get();
        if (!$arr) {
            return 0;
        }

        return array_sum($arr) / count($arr);
    }

    /**
     * First key of the array
     *
     * @return TKey|mixed
     */
    public function firstKey(mixed $undefined_value = null): mixed
    {
        $arr = $this->get();
        if (count($arr) === 0) {
            return $undefined_value;
        }

        return array_keys($arr)[0];
    }

    /**
     * @return TVal|mixed
     */
    public function first(mixed $undefined = null): mixed
    {
        $first_key = $this->firstKey();

        return ($first_key === null) ? $undefined : $this->get()[$first_key];
    }

    /**
     * @return Array2<int<0,max>,TVal>
     */
    public function firstN(int|callable $n): Array2
    {
        $arr = $this->get();
        if (is_int($n)) {
            return new self(array_slice($arr, 0, $n, true));
        }

        // Now, collect values until the $n() returns false
        $filtered = [];
        foreach ($arr as $k => $v) {
            if (!$n($v)) {
                break;
            }
            $filtered[$k] = $v;
        }

        return new self($filtered);
    }

    /**
     * @return Array2<TKey,TVal>
     */
    public function slice(int $offset, ?int $length, bool $preserve_keys = false): Array2
    {
        return new self(array_slice($this->get(), $offset, $length, $preserve_keys));
    }

    /**
     * @return Array2<TKey,TVal>
     */
    public function fromN(int $n, ?int $length = null, bool $preserve_keys = true): Array2
    {
        return new self(array_slice($this->get(), $n, $length, $preserve_keys));
    }

    public function lastKey(mixed $undefined_value = null): mixed
    {
        $arr = $this->get();
        if (count($arr) === 0) {
            return $undefined_value;
        }

        // to deal with an assoc-array
        $keys = array_keys($arr);
        return array_pop($keys);
    }

    /**
     * @return TVal|mixed
     */
    public function last(mixed $undefined = null): mixed
    {
        $last_key = $this->lastKey();

        return ($last_key === null) ? $undefined : $this->get()[$last_key];
    }

    /**
     * @return Array2<TKey, TVal>
     */
    public function lastN(int $n): Array2
    {
        return new self(array_slice($this->get(), -$n, $n, true));
    }

    public function count(): int
    {
        return count($this->get());
    }

    /**
     * @return Array2<TKey,TVal>
     */
    public function each(callable $fn): Array2
    {
        return $this->tap($fn);
    }

    /**
     * @return Array2<TKey,TVal>
     */
    public function tap(callable $fn): Array2
    {
        foreach ($this->get() as $value) {
            $fn($value);
        }

        return $this;
    }

    /**
     * a rudimentary version of array_column()
     *
     * @return Array2<int,mixed>
     */
    public function column(string $key): Array2
    {
        $arr = $this->get();

        if (count($arr) > 0) {
            $first_elm = $this->first();

            if (is_array($first_elm)) {
                $arr = array_column($arr, $key);
            } elseif (is_object($first_elm)) {
                $arr = (new Array2($arr))
                    ->filter(static fn($obj) => property_exists($obj, $key)) // @phpstan-ignore argument.type
                    ->map(static fn($O) => $O->$key)
                    ->getValues();
            } else {
                // don't know how to deal with
                $arr = array_column($arr, $key);
            }
        }

        return new self($arr);
    }

    /**
     * Replace $from values of 1st-depth with $to
     *
     * @param TVal $from
     * @param TVal $to
     * @param bool $is_strict type check
     *
     * @return Array2<TKey,TVal>
     */
    public function replaceValue(mixed $from, mixed $to, bool $is_strict = false): Array2
    {
        return $this->map(function ($i) use ($from, $to, $is_strict) {
            if ($is_strict) {
                if ($i === $from) {
                    return $to;
                }
            } elseif ($i == $from) {
                return $to;
            }

            return $i;
        });
    }

    /**
     * @return Array2<TKey,TVal>
     */
    public function unique(int $flags = SORT_STRING): Array2
    {
        // note: all values need to be "stringable"
        $arr = array_unique($this->get(), $flags);

        return new self($arr);
    }

    /**
     * @return Array2<TKey,TVal>
     */
    public function sort(?callable $fn = null): Array2
    {
        $arr = $this->get();
        if (is_callable($fn)) {
            usort($arr, $fn);
        } else {
            sort($arr);
        }

        return new self($arr);
    }

    /**
     * sort natural (case-sensitive)
     * @return Array2<TKey,TVal>
     */
    public function sortNatural(): Array2
    {
        $arr = $this->get();
        natsort($arr);

        return new self($arr);
    }

    /**
     * sort natural (case-insensitive)
     * @return Array2<TKey,TVal>
     */
    public function sortNaturalCi(): Array2
    {
        $arr = $this->get();
        natcasesort($arr);

        return new self($arr);
    }

    /**
     * Leave selected $keys only
     *
     * @template TOnlyKey of array-key
     * @param list<TOnlyKey> $keys
     *
     * @return Array2<TOnlyKey, TVal>
     */
    public function onlyKeys(array $keys): Array2
    {
        $arr = array_intersect_key($this->get(), array_flip($keys)); // https://stackoverflow.com/a/33800915/760211

        return new self($arr);
    }

    /**
     * @param list<TKey> $keys
     *
     * @return Array2<TKey,TVal>
     */
    public function dropKeys(array $keys): Array2
    {
        $arr = array_diff_key($this->get(), array_flip($keys)); // https://stackoverflow.com/a/33800915/760211

        return new self($arr);
    }

    /**
     * @param array<mixed> $arr
     *
     * @return Array2<int|string,mixed>
     */
    public function merge(...$arr): Array2
    {
        return new self(array_merge($this->get(), ...$arr));
    }

    /**
     * Hoist level-2 elements into an array
     * e.g., [ [item_1,item_2,item_3], [item_4,item_5,...] ] => [ item_1, item_2, item_3, item_4, item_5, ...]
     * @return Array2<int|string,mixed>
     */
    public function mergeValues(): Array2
    {
        /** @var array<array<mixed>> $arr */
        $arr = $this->get();
        return new self(array_merge(...$arr));
    }

    /**
     * @param null|callable(TVal):(int|float) $premap 주어질 경우 element에 map 수행
     */
    public function sum(?callable $premap = null): float|int
    {
        $arr = is_callable($premap) ? $this->map($premap)->get() : $this->get();

        return array_sum($arr);
    }

    /**
     * @param array<mixed> $arr
     *
     * @return Array2<TKey,TVal>
     */
    public function diff(array $arr): Array2
    {
        return new self(array_diff($this->get(), $arr));
    }

    /**
     * @param callable(TVal):bool $fn function($value)
     * @param mixed               $not_found
     *
     * @return TVal|mixed
     */
    public function find(callable $fn, $not_found = null): mixed
    {
        $arr = $this->get();
        foreach ($arr as $key => $val) {
            if ($fn($val)) {
                return $val;
            }
        }

        return $not_found;
    }

    /**
     * 첫번? 일치하는 [key, value]를 배열로 반환
     *
     * @param callable(TKey, TVal):bool $fn function($key, $value)
     *
     * @return array{TKey,TVal}|mixed
     */
    public function findKeyValue(callable $fn, mixed $not_found = null): mixed
    {
        $arr = $this->get();
        foreach ($arr as $key => $val) {
            if ($fn($key, $val)) {
                return [$key, $val];
            }
        }

        return $not_found;
    }

    /**
     * @param TVal $val
     *
     * @return Array2<TKey,TVal>
     */
    public function unshift($val): Array2
    {
        $arr = $this->get();
        array_unshift($arr, $val);

        return new self($arr);
    }

    /**
     * @param TVal $val
     *
     * @return Array2<TKey,TVal>
     */
    public function push($val): Array2
    {
        $arr   = $this->get();
        $arr[] = $val;

        return new self($arr);
    }

    /**
     * Note: The default value of $preserve_keys is true, while array_reverse() has false as a default.
     * @return Array2<TKey,TVal>
     */
    public function reverse(bool $preserve_keys = true): Array2
    {
        $arr = $this->get();
        $arr = array_reverse($arr, $preserve_keys);

        return new self($arr);
    }

    /**
     * @return Array2<array-key,array-key>
     */
    public function flip(): Array2
    {
        /** @var array<array-key,array-key> $arr */
        $arr = $this->get();
        $arr = array_flip($arr);

        return new self($arr);
    }

    /**
     * @template TValNew of mixed
     *
     * @param TValNew $new_placeholder_value
     *
     * @return Array2<int|string,TValNew>
     */
    public function valuesToKeys(mixed $new_placeholder_value): Array2
    {
        /** @var list<int|string> $values */
        $values = $this->getValues();
        $arr    = array_fill_keys($values, $new_placeholder_value);

        return new self($arr);
    }

    /**
     * @param callable(TVal):bool $fn
     */
    public function isAllTrue(callable $fn): bool
    {
        $arr = $this->get();
        if (!$arr) {
            return false;
        }

        return count($arr) == count(array_filter($arr, $fn));
    }

    /**
     * @param callable(TVal):bool $fn
     */
    public function isAnyTrue(callable $fn): bool
    {
        return count(array_filter($this->get(), $fn)) > 0;
    }

    /**
     * @param array<mixed> $arr
     *
     * @return Array2<TKey,TVal>
     */
    public function intersect(array $arr): Array2
    {
        $arr = array_intersect($this->get(), $arr);

        return new self($arr);
    }

    /**
     * @param string            $subkey    key for grouping
     * @param list<string>|null $labels    column labels
     * @param bool              $unset_key unset grouping key after grouping
     *
     * @return Array2<TKey,list<array<mixed>>>
     * @throws Exception
     */
    public function groupBySubkey(string $subkey, ?array $labels = null, bool $unset_key = false): Array2
    {
        $labels ??= array_values(array_unique(array_column($this->getValues(), $subkey)));

        // To maintain column order
        /** @var array<string,list<array<mixed>>> $result */
        $result = self::mapKeyValueImpl($labels, function ($idx, $key) {
            return [$key, []];
        });

        foreach ($this->get() as $row) {
            if (!is_array($row) || !array_key_exists($subkey, $row)) {
                continue;
            }

            $key_value = $row[$subkey];

            if ($unset_key) {
                unset($row[$subkey]);
            }

            $result[$key_value][] = $row;
        }

        return new self($result);
    }

    /**
     * Remove(delete) values from array. Keys are preserved.
     *
     * @param list<mixed> $values
     *
     * @return Array2<TKey,TVal>
     */
    public function removeValues(array $values, bool $strict = false): Array2
    {
        return (new self($this->get()))->filter(static fn($val) => !in_array($val, $values, $strict));
    }

    /**
     * Alias of removeValues()
     *
     * @param list<mixed> $values
     *
     * @return Array2<TKey,TVal>
     */
    public function unsetValues(array $values, bool $strict = false): Array2
    {
        return $this->removeValues($values, $strict);
    }

    /**
     * @param callable(TVal):bool $fn
     *
     * @return Array2<TKey,TVal>
     */
    public function filterRecursive(callable $fn): Array2
    {
        $arr = self::filterRecursiveImpl($this->get(), $fn);

        return new self($arr);
    }

    /**
     * @param array<TKey,TVal>|TVal     $data
     * @param callable(TVal, TKey):bool $fn
     * @param bool                      $remove_empty_array
     * @return ($data is array<TKey, TVal> ? array<TKey, TVal> : mixed)
     */
    private static function filterRecursiveImpl(mixed $data, callable $fn, bool $remove_empty_array = false): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                /** @var array<TKey,TVal> $value */
                /** @var array<TKey,TVal> $data */
                $data[$key] = $value = self::filterRecursiveImpl($value, $fn, $remove_empty_array);
                if (is_array($data[$key]) && $remove_empty_array && count($data[$key]) == 0) {
                    unset($data[$key]);
                    continue;
                }
            }

            if (!$fn($value, $key)) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
