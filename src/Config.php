<?php

namespace TAS\Core;

class Config
{
    public static $DateFormatDB = 'Y-m-d';
    public static $DateTimeFormatDB = 'Y-m-d H:i:s';
    public static $DisplayDateFormat = 'm/d/Y';
    public static $DisplayDateTimeFormat = 'm/d/Y H:i:s';
    public static $WebUI_DateCSS = 'date';
    public static $WebUI_DateTimeCSS = 'datetime';

    public static $UserRoleID = 0;

    /**
     * @var array
     */
    private static $config = [];

    public static function __constructStatic()
    {
        try {
            self::$UserRoleID = '0';
            if (isset($_SESSION['user'], $_SESSION['user']->UserRoleID)) {
                self::$UserRoleID = (int) $_SESSION['user']->UserRoleID;
            }
        } catch (\Exception $ex) {
            self::$UserRoleID = '0';
        }
    }

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

Config::__constructStatic();
