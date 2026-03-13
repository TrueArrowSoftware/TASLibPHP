<?php

namespace TAS\Core;

class IO {

    /**
     * @param string $filename
     * @return string
     */
    public static function GetExtension($filename) {
        $n = strrpos($filename,".");
        return ($n===false) ? "" : substr($filename,$n+1);
    }    
    
}