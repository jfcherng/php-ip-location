<?php

declare(strict_types=1);

/**
 * Sort an array recursively.
 *
 * @param array  $array       the array
 * @param string $sortFunc    the sort function
 * @param mixed  ...$sortArgs the sort function arguments
 *
 * @return array the sorted array
 */
function arraySortedRecursive(array $array, string $sortFunc = 'sort', ...$sortArgs): array
{
    $sortFunc($array, ...$sortArgs);

    foreach ($array as &$v) {
        if (\is_array($v)) {
            $v = (__FUNCTION__)($v, $sortFunc, ...$sortArgs);
        }
    }
    unset($v);

    return $array;
}
