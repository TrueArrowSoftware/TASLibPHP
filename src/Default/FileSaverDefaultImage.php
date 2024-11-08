<?php

namespace TAS\Core\Default;

class FileSaverDefaultImage  implements \TAS\Core\Interface\IFileSaver
{

    public \TAS\Core\ImageFile $imageObject;

    public function __construct() {}

    public function SetClassObject(\TAS\Core\UserFile &$_object)
    {
        $this->imageObject = $_object;
    }

    public function SaveFile(string $sourcepath, string &$filename): bool
    {
        if (!file_exists($sourcepath)) {
            return false;
        }

        if (empty($filename)) {
            return false;
        }

        $destinationpath = $this->imageObject->FullPath . DIRECTORY_SEPARATOR  . $filename;

        return move_uploaded_file($sourcepath, $destinationpath);
    }

    public function Copy(string $sourcepath, string $destinationpath, string $filename): bool
    {
        if (!file_exists($sourcepath)) {
            return false;
        }

        if (empty($destinationpath)) {
            return false;
        }

        return copy($sourcepath, $destinationpath . DIRECTORY_SEPARATOR . $filename);
    }

    public function GetContent(int $assetID)
    {
        //Generate Thumbnail after saving a load copy.
        $rowImage =  $GLOBALS['db']->ExecuteScalarRow("Select * From " . $GLOBALS['Tables']['images'] . " Where imageid = $assetID");

        $this->imageObject->FindFullPath($assetID);
        $folder = $this->imageObject->FindFolder($assetID);

        $fileparts = explode('.', $rowImage['imagefile']);
        $fileext = $fileparts[count($fileparts) - 1];
        unset($fileparts[count($fileparts) - 1]);
        $filenamewithoutExt = implode('.', $fileparts);

        return file_get_contents($this->imageObject->Path . "/{$folder}/" . $rowImage['imagefile']);
    }

    public function ProcessFile(int $assetID)
    {
        //Generate Thumbnail after saving a load copy.
        $rowImage =  $GLOBALS['db']->ExecuteScalarRow("Select * From " . $GLOBALS['Tables']['images'] . " Where imageid = $assetID");

        $this->imageObject->FindFullPath($assetID);
        $folder = $this->imageObject->FindFolder($assetID);

        $fileparts = explode('.', $rowImage['imagefile']);
        $fileext = $fileparts[count($fileparts) - 1];
        unset($fileparts[count($fileparts) - 1]);
        $filenamewithoutExt = implode('.', $fileparts);

        $this->imageObject->GenerateThumbnails($this->imageObject->Path . "/{$folder}/" . $rowImage['imagefile'], $filenamewithoutExt, $fileext);
    }

    public function Delete(string $path, string $filename): bool
    {
        $_file = $path . DIRECTORY_SEPARATOR . $filename; 
        if (file_exists($_file)) {
            return @unlink($_file);
        }
        return false;
    }
}
