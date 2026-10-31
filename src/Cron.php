<?php

namespace TAS\Core;

class Cron
{
    /**
     * Lock file of a script in cache/. $script is a name, not a path: "../../index.php" can't point outside cache/.
     */
    private static function LockFile($script): string
    {
        return $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'.'.basename((string) $script);
    }

    /**
     * @param $script
     * @param $timer
     * @return bool
     */
    public static function IsScriptLocked($script, $timer = 10)
    {
        $file = self::LockFile($script);
        if (file_exists($file) && filemtime($file) < strtotime('-'.(int) $timer.'min')) {
            @unlink($file);
        }

        if (file_exists($file)) {
            return true;
        }

        return false;
    }

    /**
     * @param $script
     * @return bool
     */
    public static function CreateScriptLock($script)
    {
        if (Cron::IsScriptLocked($script)) {
            return false;
        }
        $filename = self::LockFile($script);
        // 'x' only creates: when two runs get here together, one gets the lock and the other false
        $fh = @fopen($filename, 'x');
        if ($fh) {
            fwrite($fh, '1');
            fclose($fh);
            @chmod($filename, 0600); // was 0777: any local user could rewrite it

            return true;
        }

        return false;
    }

    /**
     * @param $script
     * @return bool|void
     */
    public static function UnlockScript($script)
    {
        $filename = self::LockFile($script);
        if (Cron::IsScriptLocked($script)) {
            if (@unlink($filename)) {
                return true;
            }
            \TAS\Core\Log::cLog('Fail to unlock the log');
            \TAS\Core\Log::AddEvent([
                'message' => "Fail to unlock the script {$script} ",
            ], 'high');

            return false;
        }
    }
}
