<?php

namespace TAS\Core;

/**
 * Log Error Message in system.
 *
 * @author TAS Team
 */
class Log
{
    public static function AddEvent($message, $level)
    {
        $obj=new \TAS\Core\DataFormat();
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
        $GLOBALS['db']->Insert($GLOBALS['Tables']['log'], $data);
    }

    public static function cLog($msg)
    {
        $logfile = $GLOBALS['AppConfig']['PhysicalPath'].'/cache/log.log';
        if (!file_exists($logfile)) {
            if (!file_exists(dirname($logfile))) {
                mkdir(dirname($logfile), 0777, true);
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
            echo "\r\n[".date('m-d-Y H:i:s').']: '.$msg;
        }
        fclose($fh);
    }
}
