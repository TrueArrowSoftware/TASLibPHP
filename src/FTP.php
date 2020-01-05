<?php

namespace TAS\Core;

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
     * @param unknown_type $server
     * @param unknown_type $username
     * @param unknown_type $password
     * @param unknown_type $path
     * @param unknown_type $port
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
     * @param unknown_type $errno
     * @param unknown_type $errstr
     * @param unknown_type $errfile
     * @param unknown_type $errline
     * @param unknown_type $errcontext
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

    public function Disconnect()
    {
        $this->isConnected = false;
        if ($this->isConnected) {
            @ftp_close($this->Connection);
        }
    }

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
}
