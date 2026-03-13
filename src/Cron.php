<?php

namespace TAS\Core;

class Cron
{
    /**
     * @param $script
     * @param $timer
     * @return bool
     */
    public static function IsScriptLocked($script, $timer = 10)
    {
        $file = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'.'.$script;
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
        $filename = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'.'.$script;
        $fh = fopen($filename, 'w');
        if ($fh) {
            fwrite($fh, '1');
            fclose($fh);
            chmod($filename, 0777);

            return Cron::IsScriptLocked($script);
        }

        return false;
    }

    /**
     * @param $script
     * @return bool|void
     */
    public static function UnlockScript($script)
    {
        $filename = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'.'.$script;
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
