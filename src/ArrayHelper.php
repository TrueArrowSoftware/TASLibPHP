<?php

namespace TAS\Core;

class ArrayHelper{

    public static function ArraySum(array $arr, $memberName)
    {
        if (!is_array($arr)) {
            throw new \Exception("Invalid argument.");
        }
        $output =0.0;
        array_map(function ($e) use (&$output, $memberName) {
            $output  += (float)($e->{$memberName})??0.0;
        }, $arr);
        return $output;
    }
}