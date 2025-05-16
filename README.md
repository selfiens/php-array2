# Array2 - Immutable Array Helper for PHP

[![Latest Stable Version](https://poser.pugx.org/selfiens/array2/v)](//packagist.org/packages/selfiens/array2)
[![Total Downloads](https://poser.pugx.org/selfiens/array2/downloads)](//packagist.org/packages/selfiens/array2)
[![License](https://poser.pugx.org/selfiens/array2/license)](//packagist.org/packages/selfiens/array2)

https://github.com/selfiens/php-array2

A simple, immutable array helper class for PHP. It wraps PHP's native `ArrayObject` to provide convenient, chainable methods for common array operations, without modifying the original array.

## Key Features

*   **Immutable:** The original array passed to the constructor is never changed. All manipulation methods return a *new* `Array2` instance with the modified data.
*   **Fluent Interface:** Chain methods together for clear and expressive array transformations (e.g., `$array2->filter(...)->map(...)->get()`).
*   **Extends `ArrayObject`:** Provides a familiar base and allows standard array access patterns if needed (though direct modification is discouraged to maintain immutability).
*   **Common Operations:** Includes helpful methods like `filter`, `map`, `reduce`, `sort`, `find`, `first`, `last`, `keys`, `values`, `sum`, `merge`, `groupBySubkey`, and many more.
*   **Type Hinted:** Uses PHP type hints for better code analysis and reliability.

## Installation

Install the package via Composer:

```bash
composer require selfiens/array2
```

## Basic Usage

```php
<?php

use Selfiens\Array2\Array2;

// Initial data
$data = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5];

// Create an Array2 instance
$array2 = new Array2($data);

// --- Example 1: Filtering and Mapping ---

// Get only even numbers, then double them
$result = $array2
    ->filter(fn($value) => $value % 2 === 0) // Returns a new Array2(['b' => 2, 'd' => 4])
    ->map(fn($value) => $value * 2)        // Returns a new Array2(['b' => 4, 'd' => 8])
    ->get(); // Retrieve the final underlying PHP array

print_r($result);
// Output:
// Array
// (
//     [b] => 4
//     [d] => 8
// )

// --- Example 2: Immutability Check ---

// The original array remains unchanged
print_r($data);
// Output:
// Array
// (
//     [a] => 1
//     [b] => 2
//     [c] => 3
//     [d] => 4
//     [e] => 5
// )

// --- Example 3: Other Operations ---

$firstValue = $array2->first(); // 1
$lastValue = $array2->last();   // 5
$sum = $array2->sum();          // 15 (1+2+3+4+5)
$keys = $array2->keys()->get(); // ['a', 'b', 'c', 'd', 'e']

echo "First: $firstValue, Last: $lastValue, Sum: $sum\n";
print_r($keys);

?>
```

## Immutability Explained

The core principle of `Array2` is **immutability**. When you call a method like `filter()` or `map()`, the original `Array2` object (and its underlying array) is **not** changed. Instead, a **new** `Array2` object containing the result of the operation is returned.

This approach helps prevent unintended side effects in your code, making it easier to reason about data flow and state changes.

## Available Methods

`Array2` provides a range of methods for array manipulation, including:

*   Filtering (`filter`, `filterNot`, `filterKeys`, `filterKeyValue`, `filterRecursive`, `valuesEq`, `valuesNotEq`, `removeValues`)
*   Mapping (`map`, `mapKeyValue`)
*   Reducing (`reduce`)
*   Getting Data (`get`, `all`, `keys`, `values`, `valuesFlat`, `first`, `last`, `firstN`, `lastN`, `find`, `findKeyValue`)
*   Sorting (`sort`, `sortNatural`, `sortNaturalCi`, `reverse`)
*   Slicing & Selection (`slice`, `onlyKeys`, `dropKeys`, `column`)
*   Aggregation (`sum`, `average`, `min`, `max`, `count`)
*   Modification-like (returning new instances) (`merge`, `mergeValues`, `replaceValue`, `unique`, `push`, `unshift`, `flip`, `valuesToKeys`)
*   Utility (`join`, `tap` / `each`, `isAllTrue`, `isAnyTrue`, `intersect`, `diff`, `groupBySubkey`)

Please refer to the source code (`src/Array2.php`) for the full list of methods, their parameters, and detailed PHPDoc comments.

## Testing

Run the included PHPUnit tests from the project root directory:

```bash
./vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues on the GitHub repository.

## License

This project is licensed under the MIT License - see the `LICENSE` file for details.