<?php

namespace TAS\Core;

class IO {

    public static function GetExtension($filename) {
        $n = strrpos($filename,".");
        return ($n===false) ? "" : substr($filename,$n+1);
    }    
    
}