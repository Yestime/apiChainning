<?php

namespace apiChain;

class ArrayUtils {

    /**
     * Recursively flatten nested array
     * @example [[1,2,3], [4,5]] => [1,2,3,4,5]
     * @param array $arr
     * @return array
     */
    public static function flatten($arr) {
        $result = [];
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $result = array_merge($result, self::flatten($val));
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }

    public static function setValueByPath(array $arr, $keys, $value) {
        return array_merge_recursive($arr, array_reduce(array_reverse($keys), function ($result, $key) {
            return [$key => $result];
        }, $value));
    }

    public static function allNumericKeys(array $arr) {
        $numericKeys = array_filter(array_keys($arr), function ($item) {
            return is_numeric($item);
        });

        return count($numericKeys) === count($arr);
    }

    public static function isSequential(array $arr) {
        return array_values($arr) === $arr;
    }

    public static function fillMissingNumericKeys(array $arr, $fill) {
        $maxKey = array_reduce(array_keys($arr), function ($result, $key) {
            return max($result, intval($key));
        }, -1);

        for ($i = 0; $i < $maxKey; $i += 1) {
            if ( !isset($arr[$i]) ) {
                $arr[$i] = $fill;
            }
        }

        return $arr;
    }

    public static function walkValues(&$arr, callable $callback) {
        foreach ($arr as &$val) {
            $callback($val);

            if (is_array($val)) {
                self::walkValues($val, $callback);
            }
        }
    }

    public static function last(array $arr, $default = null) {
        return count($arr) > 0 ? $arr[ count($arr) - 1 ] : $default;
    }
}