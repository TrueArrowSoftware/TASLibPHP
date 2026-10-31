<?php

namespace TAS\Core;

/**
 * Log Error Message in system.
 *
 * @author TAS Team
 */
class Log
{
    /**
     * True while the log row is being inserted. A failed DB::Insert logs, so a failing log insert would call
     * AddEvent -> Insert -> AddEvent ... forever; with this set, the nested event goes to the file instead.
     */
    private static bool $writing = false;

    /**
     * @param string|array $message
     * @param string $level
     * @return true|void
     */
    public static function AddEvent($message, $level)
    {
        $obj = new \TAS\Core\DataFormat();
        if (defined('SKIPAUTOLOADERROR')) {
            return true;
        }
        if ($message == '') {
            return true;
        }
        if (!is_array($message)) {
            $message = json_decode($message, true);
        }
        $data = array(
            'eventdate' => date('Y-m-d H:i:s'),
            'eventlevel' => \TAS\Core\DataFormat::DoSecure($level),
            'message' => $message['message'],
            'details' => json_encode($message),
            'debugtrace' => '', // print_r(debug_backtrace(),2)
        );
        if (!isset($GLOBALS['db']) || self::$writing) {
            \TAS\Core\Log::cLog($data);
        } else {
            self::$writing = true;
            try {
                $GLOBALS['db']->Insert($GLOBALS['Tables']['log'], $data);
            } catch (\Throwable $e) { // a broken log table must not take the page down with it
                \TAS\Core\Log::cLog($data);
            } finally {
                self::$writing = false;
            }
        }
    }

    /**
     * @param mixed $msg
     * @return void
     */
    public static function cLog($msg)
    {
        // AppConfig 'LogPath' puts the files outside the web root; cache/ (the default, as before) may be served
        $logfile = ($GLOBALS['AppConfig']['LogPath'] ?? $GLOBALS['AppConfig']['PhysicalPath'].'/cache').'/log'.date('Ymd').'.log';
        if (!file_exists($logfile)) {
            if (!file_exists(dirname($logfile))) {
                mkdir(dirname($logfile), 0750, true); // not 0777: other local users don't read or plant log files
            }
        }
        $fh = fopen($logfile, 'a+');
        if (is_bool($fh) && $fh == false) {
            return;
        }
        if (is_object($msg) || is_array($msg)) {
            fwrite($fh, "\r\n[".date('m-d-Y H:i:s').']: '.print_r($msg, true));
        } else {
            fwrite($fh, "\r\n[".date('m-d-Y H:i:s').']: '.$msg); 
        }
        fclose($fh);
    }
}
