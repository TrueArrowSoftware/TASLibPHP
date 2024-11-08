<?php

namespace TAS\Core;

class ArrayHelper
{
    public static function ArraySum(array $arr, $memberName)
    {
        if (!is_array($arr)) {
            throw new \Exception('Invalid argument.');
        }
        $output = 0.0;
        array_map(function ($e) use (&$output, $memberName) {
            $output += (float) $e->{$memberName} ?? 0.0;
        }, $arr);

        return $output;
    }

    /**
     * Search in 2D array for value.
     *
     * @param unknown_type $needle
     * @param unknown_type $column
     * @param unknown_type $array
     */
    public static function Search2DArray($needle, $column, $array)
    {
        foreach ($array as $key => $val) {
            if ($val[$column] == $needle) {
                return $key;
            }
        }

        return -1;
    }

    /**
     * Convert multi-dimension array to single dimension array.
     *
     * @param [type] $a
     */
    public static function SinglizeArray(array $a): array
    {
        $output = [];
        foreach ($a as $i => $k) {
            if (is_array($k)) {
                $t = \TAS\Core\ArrayHelper::SinglizeArray($k);
                foreach ($t as $i1 => $k) {
                    $output[$i . '-' . $i1] = $k;
                }
            } else {
                $output[$i] = $k;
            }
        }

        return $output;
    }

    /**
     * Contain word in array list.
     *
     * @param string $str
     *
     * @return bool
     */
    public static function Contain(string $str, array $arr)
    {
        foreach ($arr as $a) {
            if (false !== stripos($str, $a)) {
                return true;
            }
        }

        return false;
    }


    public static function ObjectToJsonLowercase($object)
    {
        // Convert object to array
        $array = json_decode(json_encode($object), true);

        // Change all keys to lowercase recursively
        $array = self::ChangeArrayKeyCase($array);

        // Convert back to JSON
        return json_encode($array);
    }


    public static function ChangeArrayKeyCase($array, $case = CASE_LOWER)
    {
        $result = [];
        foreach ($array as $key => $value) {
            // Convert key to the specified case
            $newKey = ($case == CASE_LOWER) ? strtolower($key) : strtoupper($key);

            // Recursively apply to nested arrays
            $result[$newKey] = is_array($value) ? self::ChangeArrayKeyCase($value, $case) : $value;
        }
        return $result;
    }
}
