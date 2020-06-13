<?php

namespace TAS\Core;

class UserFile
{
    public static $MAX_FILE_PER_FOLDER = 2000;
    public $Path = '';
    public $FullPath = '';
    public $BaseUrl = '';
    public $Error = '';
    public $Errors = array();
    public $FileType = 'file';

    public function __construct()
    {
        if (isset($GLOBALS['AppConfig']['UploadPath'])) {
            $this->Path = $GLOBALS['AppConfig']['UploadPath'];
        } else {
            $this->Path = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.$this->FileType;
        }
        if (isset($GLOBALS['AppConfig']['UploadURL'])) {
            $this->BaseUrl = $GLOBALS['AppConfig']['UploadURL'];
        } else {
            $this->BaseUrl = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']).'/upload/';
        }
        $GLOBALS['db'] = $GLOBALS['db'] ?? null;
    }

    /**
     * Returns the new File Name for given Extension (ex .png, .jpg).
     *
     * @param unknown $ext
     *
     * @return string
     */
    public function GetFileName($ext)
    {
        return $this->FileType.uniqid().$ext;
    }

    public function SetError($error)
    {
        if (trim($error) != '') {
            $this->Errors[] = $error;
        }
        $this->Error = $error;
    }

    public function CleanError()
    {
        $this->Errors = array();
        $this->Error = '';
    }

    public function LastError()
    {
        return $this->Error;
    }

    public function LastErrors()
    {
        return $this->Errors;
    }

    protected function Validate($file = '')
    {
        return true;
    }

    //Connect Database is not already
    public function Connect()
    {
        if (function_exists($GLOBALS['db']->Connect())) {
            return $GLOBALS['db']->Connect();
        } else {
            return false;
        }
    }

    public function FindPathForNew($Table = '')
    {
        $count = $GLOBALS['db']->FetchArray($GLOBALS['db']->Execute("SHOW TABLE STATUS LIKE '".$GLOBALS['Tables'][($Table == '' ? 'images' : $Table)]."'"));
        $count = $count['Auto_increment'];
        $count = floor($count / self::$MAX_FILE_PER_FOLDER);

        $this->FullPath = $this->Path.DIRECTORY_SEPARATOR.$this->FileType.DIRECTORY_SEPARATOR.$count;

        if (file_exists($this->FullPath)) {
            if (is_dir($this->FullPath) && is_writable($this->FullPath)) {
                return true;
            } else {
                $this->SetError('Folder is not writeable');

                return false;
            }
        } else {
            if (mkdir($this->FullPath, 0777, true)) {
                @chmod($this->FullPath, 0777);

                return true;
            } else {
                $this->SetError('Fail to create storage folder');

                return false;
            }
        }
    }

    public function FindFolder($fileid, $forURL = false)
    {
        $folder = floor($fileid / self::$MAX_FILE_PER_FOLDER);
        if ($forURL) {
            return $this->FileType.'/'.$folder;
        } else {
            return $this->FileType.DIRECTORY_SEPARATOR.$folder;
        }
    }

    public function FindFullPath($fileid)
    {
        $this->FullPath = realpath($this->Path).DIRECTORY_SEPARATOR.$this->FindFolder($fileid, false);
    }
}
