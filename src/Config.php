<?php

namespace TAS\Core;

use TAS\Core\Interface\IFileSaver;

class Config
{
    public static $DateFormatDB = 'Y-m-d';
    public static $DateTimeFormatDB = 'Y-m-d H:i:s';
    public static $DisplayDateFormat = 'm/d/Y';
    public static $DisplayDateTimeFormat = 'm/d/Y H:i:s';
    public static $WebUI_DateCSS = 'date';
    public static $WebUI_DateTimeCSS = 'datetime';

    public static $UserRoleID = 0;

    /** What __constructStatic put in $UserRoleID, so GetUserRoleID can tell whether the app set its own. */
    private static $UserRoleIDAtLoad = 0;

    /**
     * Role of the current user, read when asked. $UserRoleID is only filled when this class loads, so a login,
     * logout or role change later in the request was missed. A value the app assigned to $UserRoleID still wins.
     */
    public static function GetUserRoleID()
    {
        if (self::$UserRoleID !== self::$UserRoleIDAtLoad) {
            return self::$UserRoleID;
        }

        return isset($_SESSION['user'], $_SESSION['user']->UserRoleID) ? (int) $_SESSION['user']->UserRoleID : self::$UserRoleID;
    }

    /**
     * Escape data that may carry intended HTML: grid string/longstring/globalarray cells, HTML::ReadOnly values,
     * UI::UIMessageDisplay messages, check/radio labels and PDF report cells. Off by default so old pages look the same;
     * turn it on to close stored XSS there, and mark a grid column, form field or message with 'html' => true to keep it raw.
     * Attribute values, option text, textarea content and pager links are always escaped, whatever this says.
     */
    public static bool $EscapeOutput = false;

    public static IFileSaver $FileSaverImage;
    public static IFileSaver $FileSaverDocument;

    /**
     * @var array
     */
    private static $config = [];

    public static function __constructStatic()
    {
        self::$FileSaverImage = new Default\FileSaverDefaultImage();
        self::$FileSaverDocument = new Default\FileSaverDefaultDocument();
        
        try {

            self::$UserRoleID = '0';
            if (isset($_SESSION['user'], $_SESSION['user']->UserRoleID)) {
                self::$UserRoleID = (int) $_SESSION['user']->UserRoleID;
            }
        } catch (\Exception $ex) {
            self::$UserRoleID = '0';
        }
        self::$UserRoleIDAtLoad = self::$UserRoleID;
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
