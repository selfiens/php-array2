<?php

namespace Selfiens\Array2;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

if (!function_exists('varExport')) {
    /**
     * exports var in short array notation, with formatting
     */
    function varExport(mixed $var, string $tab = "\t", int $max_depth = 20, int $current_depth = 0): string
    {
        // memory sentinel
        if ($current_depth > $max_depth) {
            return "'truncated at {$max_depth}th recursion'";
        }

        $exp            = '';
        $obj_class_name = '';
        if (is_object($var)) {
            try {
                $obj_class_name = '/* ' . get_class($var) . ' */';
                $var            = get_object_vars($var);
            } catch (Throwable $e) {
                $var = '* ' . get_class($var) . ' obj-to-array error:' . $e->getMessage();
            }
        }

        if (is_array($var)) {
            $has_sequential_numeric_keys_only = array_is_list($var);

            foreach ($var as $key => $value) {
                // avoid infinite loop
                if ($key === 'GLOBALS') {
                    continue;
                }

                strlen($exp) > 0 && $exp .= "\n";
                $left_padding = str_repeat($tab, $current_depth + 1);
                $exp          .= $left_padding;

                $escaped_key = '';
                if (!$has_sequential_numeric_keys_only) {
                    $escaped_key = $key;
                    if (is_string($key)) {
                        $escaped_key = var_export($key, true);
                    }
                    $escaped_key .= '=>';
                }
                $exp .= $escaped_key . varExport($value, $tab, $max_depth, $current_depth + 1);
                $exp .= ",";
            }
            if (count($var) > 0) {
                $exp = $obj_class_name . "[\n" . $exp . "\n" . str_repeat($tab, $current_depth) . "]";
            } else {
                $exp = $obj_class_name . "[]";
            }
        } elseif (is_string($var) || is_bool($var) || is_float($var) || is_int($var) || is_null($var)) {
            $exp = var_export($var, true);
        } elseif (is_resource($var)) {
            $exp = "'(resource:" . get_resource_type($var) . ")'";
        } elseif (is_callable($var)) {
            if (is_string($var)) {
                // Recurse to handle string escaping properly
                $exp = varExport($var, $tab, $max_depth, $current_depth + 1);
            } elseif (is_array($var)) {
                // Recurse to handle array formatting
                $exp = varExport($var, $tab, $max_depth, $current_depth + 1);
            } elseif ($var instanceof Closure) {
                $exp = "'(Closure)'"; // Closures cannot be exported
            } else {
                $exp = "'(callable)'"; // Other callable types (e.g., object implementing __invoke)
            }
        } else // what else?
        {
            $var = gettype($var);
            $exp = "'type:$var'";
        }

        return $exp;
    }
}

if (!function_exists('varExportCompact')) {
    /**
     * exports var in short array notation
     */
    function varExportCompact(mixed $var, int $max_depth = 20, int $current_depth = 0): string
    {
        // memory sentinel
        if ($current_depth > $max_depth) {
            return "'truncated at {$max_depth}th recursion'";
        }

        $exp            = '';
        $obj_class_name = '';
        if (is_object($var)) {
            try {
                $obj_class_name = '/* ' . get_class($var) . ' */';
                $var            = get_object_vars($var);
            } catch (Throwable $e) {
                $var = '* ' . get_class($var) . ' obj-to-array error:' . $e->getMessage();
            }
        }

        if (is_array($var)) {
            $has_sequential_numeric_keys_only = array_is_list($var);

            foreach ($var as $key => $value) {
                // avoid infinite loop
                if ($key === 'GLOBALS') {
                    continue;
                }

                strlen($exp) > 0 && $exp .= ',';
                $escaped_key = '';
                if (!$has_sequential_numeric_keys_only) {
                    $escaped_key = $key;
                    if (is_string($key)) {
                        $escaped_key = var_export($key, true);
                    }
                    $escaped_key .= '=>';
                }
                $exp .= $escaped_key . varExportCompact($value, $max_depth, $current_depth + 1);
            }
            $exp = $obj_class_name . '[' . $exp . ']';
        } elseif (is_string($var) || is_bool($var) || is_float($var) || is_int($var) || is_null($var)) {
            $exp = var_export($var, true);
        } elseif (is_resource($var)) {
            $exp = "'(resource:" . get_resource_type($var) . ")'";
        } elseif (is_callable($var)) {
            if (is_string($var)) {
                // Recurse to handle string escaping properly
                $exp = varExportCompact($var, $max_depth, $current_depth + 1);
            } elseif (is_array($var)) {
                // Recurse to handle array formatting
                $exp = varExportCompact($var, $max_depth, $current_depth + 1);
            } elseif ($var instanceof Closure) {
                $exp = "'(Closure)'"; // Closures cannot be exported
            } else {
                $exp = "'(callable)'"; // Other callable types (e.g., object implementing __invoke)
            }
        } else // what else?
        {
            $var = gettype($var);
            $exp = "'type:$var'";
        }

        return $exp;
    }
}

/**
 * @template TKey of int|string
 * @template TVal of mixed
 */
class Array2Test extends TestCase
{

    public function testEach(): void
    {
        $original = ['a' => 1, 'b' => 2, 'c' => ['d' => 3, '4', null, false], 5];
        $arr      = ['a' => 1, 'b' => 2, 'c' => ['d' => 3, '4', null, false], 5];
        $actual   = [];
        (new Array2($arr))->tap(function ($item) use (&$actual) {
            $actual[] = $item;
        });

        $expected = [1, 2, ['d' => 3, '4', null, false], 5];
        $this->assertEquals($expected, $actual);
        $this->assertEquals($original, $arr);
    }

    public function testSort(): void
    {
        $arr      = [5, 4, 3, 2, 1, 0, -1];
        $actual   = (new Array2($arr))->sort()->get();
        $expected = [-1, 0, 1, 2, 3, 4, 5];
        $this->assertEquals($expected, $actual);
    }

    public function testJoin(): void
    {
        $arr      = [5, 4, 3, 2, 1, 0, -1];
        $actual   = (new Array2($arr))->join(", ");
        $expected = "5, 4, 3, 2, 1, 0, -1";
        $this->assertEquals($expected, $actual);
    }

    public function testCount(): void
    {
        $arr      = [5, 4, 3, 2, 1, 0, -1];
        $actual   = (new Array2($arr))->count();
        $expected = count($arr);
        $this->assertEquals($expected, $actual);
    }

    public function testMap(): void
    {
        $arr      = [5, 4, 3, 2, 1, 0, -1];
        $actual   = (new Array2($arr))->map(function ($item) {
            return $item + 1;
        })->get();
        $expected = [6, 5, 4, 3, 2, 1, 0];
        $this->assertEquals($expected, $actual);
    }

    public function testValuesFlat(): void
    {
        $arr    = ['a' => 1, 'b' => 2, 'c' => ['d' => 3, '4', null, false], 5];
        $actual = (new Array2($arr))->valuesFlat()->get();

        $expected = [
            0 => 1,
            1 => 2,
            2 => 3,
            3 => '4',
            4 => null,
            5 => false,
            6 => 5,
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testFilter(): void
    {
        $arr      = [5, -4, 3, -2, 1, 0, -1];
        $actual   = (new Array2($arr))->filter(function ($item) {
            return $item > 0;
        })->get();
        $expected = [0 => 5, 2 => 3, 4 => 1];
        $this->assertEquals($expected, $actual);
    }

    public function testAverage(): void
    {
        $arr      = [5, -4, 3, -2, 1, 0, -1];
        $actual   = (new Array2($arr))->average();
        $expected = 0.2857142857142857;
        $this->assertEquals($expected, $actual);
    }

    public function testReplaceValue(): void
    {
        // loose
        $arr      = ["5", -4, 3, -2, 5, 0, "5"];
        $actual   = (new Array2($arr))->replaceValue(5, "a")->get();
        $expected = ['a', -4, 3, -2, 'a', 0, 'a'];
        $this->assertEquals($expected, $actual);

        // strict
        $arr      = ["5", -4, 3, -2, 5, 0, "5"];
        $actual   = (new Array2($arr))->replaceValue(5, "a", true)->get();
        $expected = ['5', -4, 3, -2, 'a', 0, '5'];
        $this->assertEquals($expected, $actual);
    }

    public function testValues(): void
    {
        $arr    = ['a' => 1, 'b' => 2, 'c' => ['d' => 3, '4', null, false], 5];
        $actual = (new Array2($arr))->values()->get();

        $expected = [1, 2, ['d' => 3, '4', null, false], 5];
        $this->assertEquals($expected, $actual);
    }

    public function testFirstN(): void
    {
        $arr = ["5", -4, 3, -2, 5, 0, "5"];

        $actual   = (new Array2($arr))->firstN(0)->get();
        $expected = [];
        $this->assertEquals($expected, $actual);

        $actual   = (new Array2($arr))->firstN(1)->get();
        $expected = ["5"];
        $this->assertEquals($expected, $actual);

        $actual   = (new Array2($arr))->firstN(99)->get();
        $expected = ["5", -4, 3, -2, 5, 0, "5"];
        $this->assertEquals($expected, $actual);
    }

    public function testFirstN_callable(): void
    {
        $a = (new Array2([1, 3, 5, 2, 4]))->firstN(fn($n) => $n < 4)->get();
        $this->assertEquals([1, 3], $a);
    }

    public function testMapKv(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];

        $actual   = (new Array2($arr))->mapKeyValue(function ($key, $value) {
            return [$key . "x", $value + 1];
        })->get();
        $expected = ['ax' => 2, 'bx' => 3, 'cx' => 4];
        $this->assertEquals($expected, $actual);
    }

    public function testKeys(): void
    {
        $arr = [
            'a' => 11,
            'b' => 12,
            'c' => 13,
            'd' => ['e' => ['f' => 14, 'g' => 'g1']],
        ];

        $actual   = (new Array2($arr))->keys()->get();
        $expected = ['a', 'b', 'c', 'd'];
        $this->assertEquals($expected, $actual, varExportCompact($actual));
    }

    public function testFirst(): void
    {
        $arr      = ["5", -4, 3, -2, 5, 0, "5"];
        $actual   = (new Array2($arr))->first();
        $expected = "5";
        $this->assertEquals($expected, $actual);

        $arr      = [];
        $actual   = (new Array2($arr))->first("N/A");
        $expected = "N/A";
        $this->assertEquals($expected, $actual);
    }

    public function testUnique(): void
    {
        $arr      = [5, 5, 5, 1, 1, 1, 2, 2, 2, 3, 3, 3, 4, 4, 4,];
        $actual   = (new Array2($arr))->unique()->get();
        $expected = [0 => 5, 3 => 1, 6 => 2, 9 => 3, 12 => 4];
        $this->assertEquals($expected, $actual, varExportCompact($actual));
    }

    public function testGet(): void
    {
        $arr    = [0, 1, 2];
        $actual = (new Array2($arr))->get();
        $this->assertEquals($arr, $actual);
    }

    public function testFromN(): void
    {
        $arr      = range(0, 10);
        $actual   = (new Array2($arr))->fromN(5)->get();
        $expected = [5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10];
        $this->assertEquals($expected, $actual, varExportCompact($actual));
    }

    public function testReduce(): void
    {
        $arr      = range(0, 10);
        $actual   = (new Array2($arr))->reduce(fn($carry, $item) => (int)$carry + $item);
        $expected = 55;
        $this->assertEquals($expected, $actual, varExportCompact($actual));
    }

    public function testLast(): void
    {
        $arr      = range(0, 10);
        $actual   = (new Array2($arr))->last();
        $expected = 10;
        $this->assertEquals($expected, $actual, varExportCompact($actual));
    }

    public function testLastN(): void
    {
        $arr = range(0, 10);

        $actual   = (new Array2($arr))->lastN(0)->get();
        $expected = [];
        $this->assertEquals($expected, $actual, varExportCompact($actual));

        $actual   = (new Array2($arr))->lastN(1)->get();
        $expected = [10 => 10];
        $this->assertEquals($expected, $actual, varExportCompact($actual));

        $actual   = (new Array2($arr))->lastN(99)->get();
        $expected = $arr;
        $this->assertEquals($expected, $actual, varExportCompact($actual));
    }

    public function testColumn_native_array(): void
    {
        $arr = [
            ['a' => 11, 'b' => 12, 'd' => ['e' => ['f' => 14, 'g' => 'g1']]],
            ['a' => 21, 'b' => 22, 'c' => 23, 'd' => ['e' => ['f' => 24, 'g' => 'g2']]],
            ['a' => 31, 'b' => 32, 'd' => ['e' => ['f' => 34, 'g' => 'g3']]],
            ['a' => 41, 'b' => 42, 'c' => 43, 'd' => ['e' => ['f' => 44, 'g' => 'g4']]],
            ['a' => 51, 'b' => 52, 'd' => ['e' => ['f' => 54, 'g' => 'g5']]],
        ];

        $actual   = (new Array2($arr))->column('a')->get();
        $expected = [11, 21, 31, 41, 51];
        $this->assertEquals($expected, $actual, varExportCompact($actual));

        $actual   = (new Array2($arr))->column('c')->get();
        $expected = [23, 43];
        $this->assertEquals($expected, $actual, varExportCompact($actual));

        $actual   = (new Array2($arr))->column('x')->get();
        $expected = [];
        $this->assertEquals($expected, $actual, varExportCompact($actual));
    }

    public function testColumn_Object(): void
    {
        $arr = [
        ];

        $seeds = [
            ['a' => 11, 'b' => 12, 'd' => ['e' => ['f' => 14, 'g' => 'g1']]],
            ['a' => 21, 'b' => 22, 'c' => 23, 'd' => ['e' => ['f' => 24, 'g' => 'g2']]],
            ['a' => 31, 'b' => 32, 'd' => ['e' => ['f' => 34, 'g' => 'g3']]],
            ['a' => 41, 'b' => 42, 'c' => 43, 'd' => ['e' => ['f' => 44, 'g' => 'g4']]],
            ['a' => 51, 'b' => 52, 'd' => ['e' => ['f' => 54, 'g' => 'g5']]],
        ];
        foreach ($seeds as $seed_kvp) {
            $S = new stdClass();
            foreach ($seed_kvp as $key => $val) {
                $S->{$key} = $val;
            }
            $arr[] = $S;
        }

        $actual   = (new Array2($arr))->column('a')->get();
        $expected = [11, 21, 31, 41, 51];
        $this->assertEquals($expected, $actual, varExportCompact($actual));

        $actual   = (new Array2($arr))->column('c')->get();
        $expected = [23, 43];
        $this->assertEquals($expected, $actual, varExportCompact($actual));

        $actual   = (new Array2($arr))->column('x')->get();
        $expected = [];
        $this->assertEquals($expected, $actual, varExportCompact($actual));
    }

    public function testMerge(): void
    {
        $o = new Array2([1, 2, 3]);
        $o = $o->merge([3, 4, 5, 6]);
        $this->assertEquals([1, 2, 3, 3, 4, 5, 6], $o->get());
    }

    public function testFind(): void
    {
        $this->assertNull((new Array2([]))->find(function ($val) { return false; }));

        $s = ['a1', '2.0', 3, 4, 5];

        $a = (new Array2($s))->find('is_int');
        $this->assertEquals(3, $a);

        $not_found = uniqid();
        $a         = (new Array2($s))->find('is_numeric', $not_found);
        $this->assertEquals('2.0', $a);

        $a = (new Array2($s))->find(function ($val) { return is_int($val) && $val > 4; });
        $this->assertEquals(5, $a);
    }

    public function testFindShortFn(): void
    {
        $this->assertNull((new Array2([]))->find(fn($val) => false));

        $s = ['a1', 'a2', 3, 4, 5];
        $a = (new Array2($s))->find(fn($val) => is_int($val) && $val > 4);
        $this->assertEquals(5, $a);
    }

    public function testFindKeyValue(): void
    {
        $this->assertNull((new Array2([]))->findKeyValue(function ($key, $val) { return false; }));

        $s = [
            'k1' => 'a1',
            'k2' => 'a2',
            'k3' => 3,
            'k4' => 4,
            'k5' => 5,
        ];

        $a = (new Array2($s))->findKeyValue(function ($key, $val) { return is_int($val); });
        $this->assertEquals(['k3', 3], $a);

        $not_found = uniqid();
        $a         = (new Array2($s))->findKeyValue(function ($k, $v) { return !$v; }, $not_found);
        $this->assertEquals($not_found, $a);

        $a = (new Array2($s))->findKeyValue(function ($key, $val) { return $key == 'k2'; });
        $this->assertEquals(['k2', 'a2'], $a);
    }

    public function testFilterNot(): void
    {
        $s = [0, "0", false, null, "", []];
        $a = (new Array2($s))->filterNot(is_numeric(...))->get();
        $this->assertEquals([2 => false, 3 => null, 4 => "", 5 => []], $a);
    }

    /**
     * @return list<array>
     */
    public static function provideIsAllValueData(): array
    {
        return [
            [
                [], // 빈 배열은 항상 false
                function ($i) { return true; },
                false,
            ],
            [
                [1, 2, 3, 4, 5],
                function ($i) { return $i < 10; },
                true,
            ],
            [
                [1, 2, 3, 4, 5],
                function ($i) { return $i < 5; },
                false,
            ],
        ];
    }

    /**
     * @param array<TKey, TVal>   $s
     * @param callable(TVal):bool $fn
     * @param bool                $expected
     * @return void
     */
    #[DataProvider('provideIsAllValueData')]
    public function testIsAllTrue(array $s, callable $fn, $expected): void
    {
        $a = (new Array2($s))->isAllTrue($fn);
        $this->assertEquals($expected, $a);
    }

    /**
     * @return list<array>
     */
    public static function provideHasAnyValueData(): array
    {
        return [
            [
                [], // 빈 배열은 항상 false
                function ($i) { return true; },
                false,
            ],
            [
                [1, 2, 3, 4, 5],
                function ($i) { return $i < 10; },
                true,
            ],
            [
                [1, 2, 3, 4, 5],
                function ($i) { return $i < 0; },
                false,
            ],
        ];
    }

    /**
     * @param array<TKey, TVal>   $s
     * @param callable(TVal):bool $fn
     * @param bool                $expected
     * @return void
     */
    #[DataProvider('provideHasAnyValueData')]
    public function testIsAnyTrue($s, $fn, $expected)
    {
        $a = (new Array2($s))->isAnyTrue($fn);
        $this->assertEquals($expected, $a);
    }

    /**
     * @return array<mixed>
     */
    public static function provideFilterKeyValueData(): array
    {
        return [
            "empty subject"         => [
                [],
                fn($key, $val) => true,
                [],
            ],
            "assoc val true filter" => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                fn($key, $val) => true,
                ['a' => 1, 'b' => 2, 'c' => 3],
            ],
            "assoc val filter"      => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                fn($key, $val) => $val > 1,
                ['b' => 2, 'c' => 3],
            ],
            "list val filter"       => [
                [1, 2, 3],
                fn($key, $val) => $val > 1,
                [1 => 2, 2 => 3],
            ],
            "assoc key true filter" => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                fn($key, $val) => $key >= 'a',
                ['a' => 1, 'b' => 2, 'c' => 3],
            ],
            "assoc key filter"      => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                fn($key, $val) => $key > 'a',
                ['b' => 2, 'c' => 3],
            ],
            "list key filter"       => [
                [1, 2, 3],
                fn($key, $val) => $key >= 1,
                [1 => 2, 2 => 3],
            ],
        ];
    }

    /**
     * @param array<TKey, TVal>         $arr
     * @param callable(TKey, TVal):bool $fn
     * @param array<mixed>              $expected
     * @return void
     */
    #[DataProvider('provideFilterKeyValueData')]
    public function testFilterKeyValue($arr, $fn, $expected): void
    {
        $actual = (new Array2($arr))->filterKeyValue($fn)->get();
        $this->assertEquals($expected, $actual);
    }

    public function testRemoveValues(): void
    {
        $s = [1, 2, 3, 4, 'a', 'b', 0, null, false];

        $s1 = $s;
        $this->assertEquals([1, 2, 3, 4, 'a', 'b'], (new Array2($s1))->removeValues([0])->get());
        $this->assertEquals([1, 2, 3, 4, 'a', 'b', 7 => null, 8 => false],
            (new Array2($s1))->removeValues([0], true)->get());
    }


    /**
     * @return list<array>
     */
    public static function provideSumData(): array
    {
        return [
            [
                [1, 2], // in
                fn(int $x) => $x, // fn
                3, // expected
            ],
            [
                [],
                fn($x) => $x,
                0,
            ],
            [
                range(0, 10),
                fn(int $x) => $x,
                55,
            ],
            [
                range(0, 10),
                fn(int $x) => $x * 2,
                110,
            ],
        ];
    }

    /**
     * @param list<int>                 $arr
     * @param callable(int):(int|float) $premap
     * @param int                       $expected
     * @return void
     */
    #[DataProvider('provideSumData')]
    public function testSum($arr, callable $premap, $expected)
    {
        $actual = (new Array2($arr))->sum($premap);
        $this->assertEquals($expected, $actual);
    }
}
