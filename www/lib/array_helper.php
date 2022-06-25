<?php

declare(strict_types=1);


/**
 * Checks if the given keys exist in the array
 * @param  array $keys  Values to check
 * @param  array $array  An array with keys to check
 * @return bool  True if all keys exist, otherwise false
 */
function array_keys_exist(array $keys, array $array): bool
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $array)) {
            return false;
        }
    }
    return true;
}


/**
 * Converts array elements to integer
 *
 * @param array $array  An array to convert
 * @param ?array $keys  If not null, convert only values of these keys
 * @return array Array where elements are converted to integer
 */
function array_values_to_int(array $array, ?array $keys=null): array
{
    if ($keys === null) {
        return array_map("intval", $array);
    } else {
        foreach ($keys as $key) {
            $array[$key] = intval($array[$key]);
        }
        return $array;
    }
}


/**
 * Converts array elements to boolean
 *
 * @param array $array  An array to convert
 * @param ?array $keys  If not null, convert only values of these keys
 * @return array Array where elements are converted to boolean
 */
function array_values_to_bool(array $array, ?array $keys=null): array
{
    if ($keys === null) {
        return array_map("boolval", $array);
    } else {
        foreach ($keys as $key) {
            $array[$key] = boolval($array[$key]);
        }
        return $array;
    }
}


/**
 * Converts array elements to string
 *
 * @param array $array  An array to convert
 * @param ?array $keys  If not null, convert only values of these keys
 * @return array Array where elements are converted to string
 */
function array_values_to_string(array $array, ?array $keys=null): array
{
    if ($keys === null) {
        return array_map("strval", $array);
    } else {
        foreach ($keys as $key) {
            $array[$key] = strval($array[$key]);
        }
        return $array;
    }
}
