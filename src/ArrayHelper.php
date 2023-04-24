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
                $t = \TAS\Core\Utility::SinglizeArray($k);
                foreach ($t as $i1 => $k) {
                    $output[$i.'-'.$i1] = $k;
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
     * @param unknown $str
     *
     * @return bool
     */
    public static function Contain($str, array $arr)
    {
        foreach ($arr as $a) {
            if (false !== stripos($str, $a)) {
                return true;
            }
        }

        return false;
    }
}
