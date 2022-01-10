<?php

namespace TAS\Core;

class IO
{
    public static function GetExtension($filename)
    {
        $n = strrpos($filename, '.');

        return (false === $n) ? '' : substr($filename, $n + 1);
    }
}
