<?php

namespace TAS\Core;

use TAS\Core\Async\FiberRunner;

/**
 * Wrapper class for PHP FTP.
 */
class FTP
{
    public $FTPServer;
    public $FTPPort;
    public $Username;
    public $Password;
    public $RemotePath;
    private $isConnected;
    private $Connection;

    /**
     * Construct.
     *
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $path
     * @param string|int $port
     */
    public function __construct($server, $username, $password, $path = '/', $port = '21')
    {
        $this->FTPServer = $server;
        $this->Username = $username;
        $this->Password = $password;
        $this->FTPPort = $port;
        $this->RemotePath = $path;
        $this->isConnected = false;
    }

    public function __destruct()
    {
        if ($this->isConnected) {
            $this->Disconnect();
        }
    }

    /**
     * FTP Class' own Error handler to Enable Capturing FTP warning.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     */
    public static function FTPErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        echo 'error:: '.$errno.'::'.$errstr.'::'.$errfile.'::'.print_r($errcontext, true);
        switch ($errno) {
            case E_USER_ERROR:
            case E_USER_WARNING:
                \TAS\Core\Log::AddEvent(json_encode(array('FTP Connection Fail to invalid Information', $errstr, '')), 'normal');
                break;
        }
    }

    /**
     * Connection.
     * @return bool
     */
    public function Connect()
    {
        try {
            if ($this->isConnected) {
                return true;
            }
            //	set_error_handler("FTP::FTPErrorHandler");
            $this->Connection = @ftp_connect($this->FTPServer, $this->FTPPort, 20) or die('Connection failed'); //REMARK: We set timeout as 20 second to ensure it fail sooner
            if (ftp_login($this->Connection, $this->Username, $this->Password)) {
                @ftp_chdir($this->Connection, $this->RemotePath);
                $this->isConnected = true;

                return true;
            }

            return false;
        } catch (\Exception $ex) {
            return false;
        }
        //	restore_error_handler();
    }

    /**
     * @return void
     */
    public function Disconnect()
    {
        $this->isConnected = false;
        if ($this->isConnected) {
            @ftp_close($this->Connection);
        }
    }

    /**
     * @param string $localpath
     * @param string $remotepath
     * @param int $mode
     * @return bool
     */
    public function Put($localpath, $remotepath, $mode = FTP_BINARY)
    {
        if (!$this->isConnected) {
            $this->Connect();
        }
        if ($this->isConnected) {
            if (@ftp_put($this->Connection, $remotepath, $localpath, $mode)) {
                return true;
            } else {
                \TAS\Core\Log::AddEvent(json_encode(array("Unable to upload $localpath to remote server", print_r($this, true))), 'normal');

                return false;
            }
        } else {
            \TAS\Core\Log::AddEvent(json_encode(array("Unable to upload $localpath to remote server as fail to connect to server", print_r($this, true))), 'normal');

            return false;
        }
    }

    /**
     * @param string $remotepath
     * @return bool
     */
    public function Delete($remotepath)
    {
        if (!$this->isConnected) {
            $this->Connect();
        }
        if ($this->isConnected) {
            if (@ftp_delete($this->Connection, $remotepath)) {
                return true;
            } else {
                return false;
            }
        } else {
            \TAS\Core\Log::AddEvent(json_encode(array('Unable to delete $remotepath from remote server as connection is not established', print_r($this, true))), 'normal');

            return false;
        }
    }

    /**
     * @param string $localpath
     * @param string $remotepath
     * @param int $mode
     * @return bool
     */
    public function Get($localpath, $remotepath, $mode = FTP_BINARY)
    {
        if (!$this->isConnected) {
            $this->Connect();
        }
        if ($this->isConnected) {
            if (ftp_get($this->Connection, $localpath, $remotepath, $mode)) {
                return true;
            } else {
                \TAS\Core\Log::AddEvent(json_encode(array("Unable to download $localpath from remote server [ $remotepath ]", print_r($this, true))), 'normal');

                return false;
            }
        } else {
            \TAS\Core\Log::AddEvent(json_encode(array("Unable to download $localpath from remote server [ $remotepath ] as fail to connect to server", print_r($this, true))), 'normal');

            return false;
        }
    }

    /**
     * @param string $remotedirectory
     * @param bool $tryPassive
     * @return mixed
     */
    public function GetRawList($remotedirectory = '', $tryPassive = true)
    {
        if (!$this->isConnected) {
            $this->Connect();
        }
        if ($this->isConnected) {
            ftp_pasv($this->Connection, false);
            $content = ftp_rawlist($this->Connection, ' -a '.($remotedirectory == '' ? $this->RemotePath : $remotedirectory));
            if ($tryPassive) {
                //try to do passive mode.
                if (isset($GLOBALS['AppConfig']['Debug']) && $GLOBALS['AppConfig']['Debug']) {
                    echo 'FTP is in Passive mode';
                }
                ftp_pasv($this->Connection, true);
                $content = ftp_rawlist($this->Connection, ' -a '.($remotedirectory == '' ? $this->RemotePath : $remotedirectory));
            }

            return $content;
        } else {
            return false;
        }
    }

    /**
     * @param string $remotedirectory
     * @param bool $trypassive
     * @return array|bool
     */
    public function GetList($remotedirectory = '', $trypassive = true)
    {
        $list = $this->GetRawList($remotedirectory, $trypassive);
        if ($list != false) {
            $fileList = array();
            foreach ($list as $index => $value) {
                $output = array();
                preg_match('/([drwx-]{10})\s*(\d*)\s*([^\s]*)\s*([^\s]*)\s*(\d*)\s*([A-Za-z]{3}\s*[0-9]{1,2})\s*([0-9:]*)\s*(.*)$/i',
                $value, $output);
                $fileList[] = $output;
            }

            return $fileList;
        } else {
            return false;
        }
    }

    /**
     * Upload multiple files in parallel using Fibers.
     *
     * Each file gets its own FTP connection for true concurrent transfer.
     *
     * @param array $files Array of ['local' => localPath, 'remote' => remotePath] entries
     * @param int   $mode  FTP transfer mode (FTP_BINARY or FTP_ASCII)
     * @return array<int, bool> Results keyed by file index. True = success, false = failure.
     */
    public static function PutBatch(array $files, string $server, string $username, string $password, string $path = '/', string $port = '21', int $mode = FTP_BINARY): array
    {
        if (empty($files)) {
            return [];
        }

        if (count($files) === 1 || !FiberRunner::isSupported()) {
            // Sequential fallback
            $results = [];
            $ftp = new FTP($server, $username, $password, $path, $port);
            $ftp->Connect();
            foreach ($files as $key => $file) {
                $results[$key] = $ftp->Put($file['local'], $file['remote'], $mode);
            }
            return $results;
        }

        $tasks = [];
        foreach ($files as $key => $file) {
            $tasks[$key] = function () use ($server, $username, $password, $path, $port, $file, $mode) {
                $ftp = new FTP($server, $username, $password, $path, $port);
                if ($ftp->Connect()) {
                    $result = $ftp->Put($file['local'], $file['remote'], $mode);
                    $ftp->Disconnect();
                    return $result;
                }
                return false;
            };
        }

        $outcome = FiberRunner::runSettled($tasks);
        $results = [];
        foreach ($files as $key => $file) {
            $results[$key] = $outcome['results'][$key] ?? false;
        }
        return $results;
    }

    /**
     * Download multiple files in parallel using Fibers.
     *
     * Each file gets its own FTP connection for true concurrent transfer.
     *
     * @param array  $files    Array of ['local' => localPath, 'remote' => remotePath] entries
     * @param string $server   FTP server
     * @param string $username FTP username
     * @param string $password FTP password
     * @param string $path     Remote base path
     * @param string $port     FTP port
     * @param int    $mode     FTP transfer mode
     * @return array<int, bool> Results keyed by file index
     */
    public static function GetBatch(array $files, string $server, string $username, string $password, string $path = '/', string $port = '21', int $mode = FTP_BINARY): array
    {
        if (empty($files)) {
            return [];
        }

        if (count($files) === 1 || !FiberRunner::isSupported()) {
            $results = [];
            $ftp = new FTP($server, $username, $password, $path, $port);
            $ftp->Connect();
            foreach ($files as $key => $file) {
                $results[$key] = $ftp->Get($file['local'], $file['remote'], $mode);
            }
            return $results;
        }

        $tasks = [];
        foreach ($files as $key => $file) {
            $tasks[$key] = function () use ($server, $username, $password, $path, $port, $file, $mode) {
                $ftp = new FTP($server, $username, $password, $path, $port);
                if ($ftp->Connect()) {
                    $result = $ftp->Get($file['local'], $file['remote'], $mode);
                    $ftp->Disconnect();
                    return $result;
                }
                return false;
            };
        }

        $outcome = FiberRunner::runSettled($tasks);
        $results = [];
        foreach ($files as $key => $file) {
            $results[$key] = $outcome['results'][$key] ?? false;
        }
        return $results;
    }
}
