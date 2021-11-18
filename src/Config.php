<?php

namespace TAS\Core;

class Config
{
    public static $DateFormatDB = 'Y-m-d';
    public static $DateTimeFormatDB = 'Y-m-d H:i:s';
    public static $DisplayDateFormat = 'm/d/Y';
    public static $DisplayDateTimeFormat = 'm/d/Y H:i:s';

    /**
     * @var array
     */
    private static $config = [];

    /**
     * @param string $key
     * @param mixed  $value
     */
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public static function get($key)
    {
        return self::$config[$key] ?? null;
    }
}
