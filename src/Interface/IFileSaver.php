<?php

namespace TAS\Core\Interface;

/**
 * Interface used by UserFile to save file. Default Integration do the Move_uploaded_file, but you can store to different location or to Cloud storage like S3 or Azure Blob using this method.
 */
interface IFileSaver
{
    /**
     * Save the file in desginated service. It doesn't bother file type. 
     *
     * @param string $sourcepath
     * @param string $destinationpath
     * @return bool
     */
    public function SaveFile(string $sourcepath, string &$destinationpath): bool;

    public function Copy(string $sourcepath,  string $destinationpath, string $filename): bool;
 
    /**
     * Returns the file read from destination path or cloud.
     *
     * @param string $destinationpath
     * @param string|null $baseURL
     * @return 
     */
    public function GetContent(int $assetID);

    public function ProcessFile(int $assetID);

    public function SetClassObject(\TAS\Core\UserFile &$_object);

    public function Delete(string $path, string $filename): bool;
}
