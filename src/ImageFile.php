<?php

namespace TAS\Core;

use TAS\Core\Interface\IFileSaver;

class ImageFile extends UserFile
{
    public $LinkerType = '';
    public IFileSaver $FileSaver;

    public $ThumbnailSize = [
        0 => [
            'width' => 400,
            'height' => 300,
        ],
        1 => [
            'width' => 100,
            'height' => 100,
        ],
        2 => [
            'width' => 240,
            'height' => 180,
        ],
        3 => [
            'width' => 300,
            'height' => 300,
        ],
    ];

    // Should be in form of [0][width]=450, [0][height]=330 etc . works only in case of save function.
    private $ThumbnailCollection = [];

    public function __construct()
    {
        parent::__construct();
        $this->BaseUrl = ($GLOBALS['AppConfig']['UploadURL'] ?? $GLOBALS['AppConfig']['HomeURL']);
        $this->Path = ($GLOBALS['AppConfig']['UploadPath'] ?? $GLOBALS['AppConfig']['PhysicalPath']);

        $this->FileType = 'image';
        $this->LinkerType = 'product';

        $this->FileSaver = \TAS\Core\Config::$FileSaverImage;
        $this->FileSaver->SetClassObject($this);
    }

    /**
     * Validate if the file is image file or application-octet-stream, all other type will fail.
     *
     * @param string $file
     */
    public function Validate($file = '')
    {
        if ('' == $file) {
            return false;
        }
        if (!empty($file['type']) && (!is_bool(strpos($file['type'], 'image')) || 'application/octet-stream' == $file['type'])) {
            return true;
        }

        return false;
    }


    // Function To Upload File from $_FILES replicated Array.
    // Also Save files if second parameter is true
    public function Upload($file, $save = true, $linkerid = 0)
    {
        $returnfile = [];
        if (!is_array($file)) {
            return false;
        }

        foreach ($file as $key => $filedata) {
            if (!is_array($filedata)) {
                continue;
            }
            if ($this->FindPathForNew()) {
                $fileext = explode('.', $filedata['name']);
                $fileext = $fileext[count($fileext) - 1];
                if ($this->Validate($filedata)) {
                    $filenamewithoutExt = $this->getFileName('.');
                    $filename = $filenamewithoutExt . $fileext;
                    $destination = $this->FullPath . DIRECTORY_SEPARATOR . $filename;
                    if ($this->FileSaver->SaveFile($filedata['tmp_name'], $filename)) {
                        $filedata['UploadStatus'] = true;
                        if ($save) {

                            //$this->GenerateThumbnails($destination, $filenamewithoutExt, $fileext);
                            $outID = 0;
                            if ($this->Save($filename, $filedata, $linkerid, $outID)) {
                                $this->FileSaver->ProcessFile($outID);

                                $filedata['UploadStatus'] = true;
                            } else {
                                $this->SetError('Fail to save in database (File :' . $filedata['name'] . ' ::' . print_r($GLOBALS['db']->LastErrors(), true) . ' )');
                                $filedata['UploadStatus'] = false;
                            }
                        }
                    } else {
                        $filedata['UploadStatus'] = false;
                        $this->SetError('Unable to save ' . $filedata['name']);

                        continue;
                    }
                } else {
                    $filedata['UploadStatus'] = false;
                    $this->SetError($filedata['name'] . ' fails to validate security check');

                    continue;
                }
            } else {
                $filedata['UploadStatus'] = false;

                continue;
            }
            $returnfile[$key] = $filedata;
        }

        return $returnfile;
    }

    /**
     * Save the file.
     *
     * @param [type] $file
     * @param [type] $filedata
     * @param string $linkerid
     * @return void
     */
    public function Save($file, $filedata, $linkerid = '', &$outID = 0)
    {
        $InsertData['imagecaption'] = $filedata['caption'];
        $InsertData['imagefile'] = $file;
        $InsertData['thumbnailfile'] = json_encode($this->ThumbnailCollection);
        $InsertData['linkerid'] = $linkerid;
        $InsertData['linkertype'] = $this->LinkerType;
        $InsertData['status'] = $filedata['status'];
        $InsertData['updatedate'] = date('Y-m-d H:i:s');
        $InsertData['isdefault'] = ($filedata['isdefault'] ?? 0);
        $InsertData['tag'] = $filedata['tag'] ?? '';
        $InsertData['settings'] = (isset($filedata['settings']) ? json_encode($filedata['settings']) : '');
        $InsertData['folderid'] = (isset($filedata['folderid']) ? (int)$filedata['folderid'] : 0);
        $displayOrder = $GLOBALS['db']->ExecuteScalar('select max(displayorder)+1 from ' . $GLOBALS['Tables']['images'] . " where linkertype='" . $this->LinkerType . "' and linkerid='" . $linkerid . "'");
        if ('' == $displayOrder) {
            $displayOrder = 1;
        }
        $InsertData['displayorder'] = $displayOrder;

        if (isset($filedata['recordid']) && $filedata['recordid'] > 0) {
            if ($GLOBALS['db']->UpdateArray($GLOBALS['Tables']['images'], $InsertData, $filedata['recordid'], 'imageid')) {
                $outID = $filedata['recordid'];
                return true;
            }

            return false;
        }
        $InsertData['adddate'] = date('Y-m-d H:i:s');
        if ($GLOBALS['db']->Insert($GLOBALS['Tables']['images'], $InsertData)) {
            $outID = $GLOBALS['db']->GeneratedID();
            return true;
        }

        return false;
    }

    /**
     * Return images on given LinkerID, however it does use object's linkertype to determine the image, which should be set in LinkerType.
     *
     * @param int    $linkerid ID to which this record is associated
     * @param bool   $toponly  If we find only the first record in order
     * @param string $orderby  default ordering
     */
    public function GetImageOnLinker(int $linkerid, bool $toponly = false, string $orderby = 'isdefault DESC, displayorder asc')
    {
        $images = [];
        if ($toponly) {
            $imagelist = $GLOBALS['db']->Execute('Select * from ' . $GLOBALS['Tables']['images'] . " where linkertype='" . $this->LinkerType . "' and linkerid={$linkerid} order by {$orderby} limit 1");
        } else {
            $imagelist = $GLOBALS['db']->Execute('Select * from ' . $GLOBALS['Tables']['images'] . " where linkertype='" . $this->LinkerType . "' and linkerid={$linkerid} order by {$orderby}");
        }
        if ($GLOBALS['db']->RowCount($imagelist) > 0) {
            while ($rowImage = $GLOBALS['db']->FetchArray($imagelist)) {
                $folder = $this->FindFolder($rowImage['imageid']);
                $images[$rowImage['imageid']]['ImageID'] = $rowImage['imageid'];
                $images[$rowImage['imageid']]['filename'] = $rowImage['imagefile'];
                $images[$rowImage['imageid']]['caption'] = $rowImage['imagecaption'];
                $images[$rowImage['imageid']]['url'] = $this->BaseUrl . '/' . $this->FindFolder($rowImage['imageid'], true) . '/' . $rowImage['imagefile'];
                $images[$rowImage['imageid']]['physicalpath'] = $this->Path . DIRECTORY_SEPARATOR . "{$folder}" . DIRECTORY_SEPARATOR . $rowImage['imagefile'];
                $images[$rowImage['imageid']]['isdefault'] = $rowImage['isdefault'];
                $images[$rowImage['imageid']]['adddate'] = $rowImage['adddate'];
                $images[$rowImage['imageid']]['updatedate'] = $rowImage['updatedate'];
                $images[$rowImage['imageid']]['status'] = $rowImage['status'];
                $images[$rowImage['imageid']]['tag'] = $rowImage['tag'];
                $images[$rowImage['imageid']]['displayorder'] = $rowImage['displayorder'];
                $images[$rowImage['imageid']]['settings'] = $rowImage['settings'];
                $images[$rowImage['imageid']]['thumbnails'] = @json_decode($rowImage['thumbnailfile'], true);
                $images[$rowImage['imageid']]['baseurl'] = $this->BaseUrl . '/' . $this->FindFolder($rowImage['imageid'], true) . '/';
                $images[$rowImage['imageid']]['folderid'] = (isset($rowImage['folderid']) ? (int)$rowImage['folderid'] : 0);

                if (!is_array($images[$rowImage['imageid']]['thumbnails']) || (count($images[$rowImage['imageid']]['thumbnails']) < 1 && count($this->ThumbnailSize) > 0)) {
                    $this->FindFullPath($rowImage['imageid']);
                    $this->FileSaver->ProcessFile($rowImage['imageid']);
                    $GLOBALS['db']->Execute('update ' . $GLOBALS['Tables']['images'] . " set thumbnailfile='" . json_encode($this->ThumbnailCollection) . "' where imageid=" . $rowImage['imageid']);
                    $images[$rowImage['imageid']]['thumbnails'] = $this->ThumbnailCollection;
                }
            }
        }

        if ($toponly) {
            return array_shift($images);
        }

        return $images;
    }

    /**
     * Overloaded function for GetImageOnLinker but with LinkerType as option.
     */
    public static function GetLinkerImage(int $linkerID, string $linkerType, bool $topOnly = false, string $orderby = 'isdefault DESC, displayorder asc')
    {
        $files = new ImageFile();
        $files->LinkerType = $linkerType;

        return $files->GetImageOnLinker($linkerID, $topOnly, $orderby);
    }

    /**
     * Return all image of object's linker type.
     * Setting TopOnly to true will return first image.
     */
    public function GetImageLinkerType(bool $toponly = false, string $orderby = 'isdefault DESC, displayorder asc')
    {
        $images = [];
        if ($toponly) {
            $imagelist = $GLOBALS['db']->Execute('Select * from ' . $GLOBALS['Tables']['images'] . " where linkertype='" . $this->LinkerType . "' order by {$orderby} limit 1");
        } else {
            $imagelist = $GLOBALS['db']->Execute('Select * from ' . $GLOBALS['Tables']['images'] . " where linkertype='" . $this->LinkerType . "' order by {$orderby}");
        }
        if ($GLOBALS['db']->RowCount($imagelist) > 0) {
            while ($rowImage = $GLOBALS['db']->FetchArray($imagelist)) {
                $folder = $this->FindFolder($rowImage['imageid']);
                $images[$rowImage['imageid']]['LinkerID'] = $rowImage['linkerid'];
                $images[$rowImage['imageid']]['ImageID'] = $rowImage['imageid'];
                $images[$rowImage['imageid']]['filename'] = $rowImage['imagefile'];
                $images[$rowImage['imageid']]['caption'] = $rowImage['imagecaption'];
                $images[$rowImage['imageid']]['url'] = $this->BaseUrl . '/' . $this->FindFolder($rowImage['imageid'], true) . '/' . $rowImage['imagefile'];
                $images[$rowImage['imageid']]['physicalpath'] = $this->Path . DIRECTORY_SEPARATOR . "{$folder}" . DIRECTORY_SEPARATOR . $rowImage['imagefile'];
                $images[$rowImage['imageid']]['isdefault'] = $rowImage['isdefault'];
                $images[$rowImage['imageid']]['adddate'] = $rowImage['adddate'];
                $images[$rowImage['imageid']]['updatedate'] = $rowImage['updatedate'];
                $images[$rowImage['imageid']]['status'] = $rowImage['status'];
                $images[$rowImage['imageid']]['tag'] = $rowImage['tag'];
                $images[$rowImage['imageid']]['displayorder'] = $rowImage['displayorder'];
                $images[$rowImage['imageid']]['settings'] = $rowImage['settings'];
                $images[$rowImage['imageid']]['thumbnails'] = @json_decode($rowImage['thumbnailfile'], true);
                $images[$rowImage['imageid']]['baseurl'] = $this->BaseUrl . '/' . $this->FindFolder($rowImage['imageid'], true) . '/';
                $images[$rowImage['imageid']]['folderid'] = (isset($rowImage['folderid']) ? (int)$rowImage['folderid'] : 0);

                if (!is_array($images[$rowImage['imageid']]['thumbnails']) || (count($images[$rowImage['imageid']]['thumbnails']) < 1 && count($this->ThumbnailSize) > 0)) {
                    $this->FindFullPath($rowImage['imageid']);
                    $this->FileSaver->ProcessFile($rowImage['imageid']);
                    $GLOBALS['db']->Execute('update ' . $GLOBALS['Tables']['images'] . " set thumbnailfile='" . json_encode($this->ThumbnailCollection) . "' where imageid=" . $rowImage['imageid']);
                    $images[$rowImage['imageid']]['thumbnails'] = $this->ThumbnailCollection;
                }
            }
        }
        if ($toponly) {
            return array_shift($images);
        }

        return $images;
    }

    /**
     * Find image on given ImageID.
     *
     * @param [type] $imageid
     * @param string $orderby
     *
     * @return array
     */
    public function GetImage($imageid, $orderby = 'isdefault DESC, displayorder asc')
    {
        $images = [];
        if ((int) $imageid <= 0) {
            return null;
        }
        $imagelist = $GLOBALS['db']->Execute('Select * from ' . $GLOBALS['Tables']['images'] . " where imageid={$imageid} order by {$orderby}");
        if ($GLOBALS['db']->RowCount($imagelist) > 0) {
            while ($rowImage = $GLOBALS['db']->FetchArray($imagelist)) {
                $folder = $this->FindFolder($rowImage['imageid']);
                $URLfolder = $this->FindFolder($rowImage['imageid'], true);
                $images[$rowImage['imageid']]['filename'] = $rowImage['imagefile'];
                $images[$rowImage['imageid']]['caption'] = $rowImage['imagecaption'];
                $images[$rowImage['imageid']]['url'] = $this->BaseUrl . "/{$URLfolder}/" . $rowImage['imagefile'];
                $images[$rowImage['imageid']]['baseurl'] = $this->BaseUrl . "/{$URLfolder}/";
                $images[$rowImage['imageid']]['physicalpath'] = $this->Path . "/{$folder}/" . $rowImage['imagefile'];
                $images[$rowImage['imageid']]['isdefault'] = $rowImage['isdefault'];
                $images[$rowImage['imageid']]['adddate'] = $rowImage['adddate'];
                $images[$rowImage['imageid']]['updatedate'] = $rowImage['updatedate'];
                $images[$rowImage['imageid']]['status'] = $rowImage['status'];
                $images[$rowImage['imageid']]['tag'] = $rowImage['tag'];
                $images[$rowImage['imageid']]['displayorder'] = $rowImage['displayorder'];
                $images[$rowImage['imageid']]['settings'] = $rowImage['settings'];
                $images[$rowImage['imageid']]['thumbnails'] = @json_decode($rowImage['thumbnailfile'], true);
                $images[$rowImage['imageid']]['folderid'] = (isset($rowImage['folderid']) ? (int)$rowImage['folderid'] : 0);

                if (!is_array($images[$rowImage['imageid']]['thumbnails']) || (count($images[$rowImage['imageid']]['thumbnails']) < 1 && count($this->ThumbnailSize) > 0)) {
                    $this->FileSaver->ProcessFile($rowImage['imageid']);
                    $GLOBALS['db']->Execute('update ' . $GLOBALS['Tables']['images'] . " set thumbnailfile='" . json_encode($this->ThumbnailCollection) . "' where imageid=" . $rowImage['imageid']);
                    $images[$rowImage['imageid']]['thumbnails'] = $this->ThumbnailCollection;
                }
            }
        } else {
            $images = null;
        }

        return $images;
    }

    /**
     *  Function to delete Image on Linker.
     *
     * @param mixed $linkerid
     */
    public function DeleteImageOnLinker($linkerid)
    {
        $imagelist = $GLOBALS['db']->Execute('Select imageid from ' . $GLOBALS['Tables']['images'] . " where linkertype='" . $this->LinkerType . "' and linkerid={$linkerid}");
        if ($GLOBALS['db']->RowCount($imagelist) > 0) {
            foreach ($imagelist as $row) {
                $this->DeleteImage($row['imageid']);
            }
        }
    }

    /**
     *  Function to delete Image on Linker.
     */
    public function DeleteImage(int $imageid)
    {
        $images = [];
        $imagelist = $GLOBALS['db']->Execute('Select * from ' . $GLOBALS['Tables']['images'] . " where imageid={$imageid}");
        // Remove Physical File
        if ($GLOBALS['db']->RowCount($imagelist) > 0) {
            while ($rowImage = $GLOBALS['db']->FetchArray($imagelist)) {
                $folder = $this->FindFolder($rowImage['imageid']);
                $this->FileSaver->Delete($this->Path, "{$folder}/" . $rowImage['imagefile']);

                if ('' != $rowImage['thumbnailfile']) {
                    $thumbnail = json_decode($rowImage['thumbnailfile'], true);
                    if (is_array($thumbnail)) {
                        foreach ($this->ThumbnailSize as $key => $Size) {
                            if (isset($thumbnail['w' . $Size['width'] . '.h' . $Size['height']])) {
                                $this->FileSaver->Delete($this->Path, "{$folder}/" . $thumbnail['w' . $Size['width'] . '.h' . $Size['height']]);
                            }
                        }
                    }
                }
            }
        }
        // Clean From DB
        $GLOBALS['db']->Execute('Delete from ' . $GLOBALS['Tables']['images'] . " where imageid={$imageid}");

        return true;
    }

    public function SetDefautlImage($imageId, $linkerId)
    {
        if (is_numeric($imageId) && $imageId > 0 && is_numeric($linkerId) && $linkerId > 0) {
            // Unset all defaults
            $imagelist = $GLOBALS['db']->Execute('Select * from ' . $GLOBALS['Tables']['images'] . " where imageid={$imageId}");

            if ($GLOBALS['db']->RowCount($imagelist) > 0) {
                $GLOBALS['db']->Execute('update ' . $GLOBALS['Tables']['images'] . " set isdefault= 0 where linkerid={$linkerId} and linkertype='" . $this->LinkerType . "'");
                if ($GLOBALS['db']->Execute('update ' . $GLOBALS['Tables']['images'] . " set isdefault= 1 where linkerid={$linkerId} and linkertype='" . $this->LinkerType . "' and imageid={$imageId}")) {
                    return true;
                }
                $this->SetError('Invalid data to set default image ');

                return false;
            }
            $this->SetError('Invalid data to set default image ');

            return false;
        }
        $this->SetError('Invalid data to set default image ');

        return false;
    }

    public function SetImageCaption($ImageId, $newCaption)
    {
        if (!empty($ImageId) && $ImageId > 0 && '' != $newCaption) {
            $imagelist = $GLOBALS['db']->Execute('Select * from ' . $GLOBALS['Tables']['images'] . " where imageid={$ImageId}");
            if ($GLOBALS['db']->RowCount($imagelist) > 0) {
                $GLOBALS['db']->Execute('update ' . $GLOBALS['Tables']['images'] . " set imagecaption='" . $newCaption . "' where imageid={$ImageId}");

                return true;
            }
            $this->SetError('Invalid data to change image caption');

            return false;
        }
        $this->SetError('Invalid data to change image caption');
    }

    public function GenerateThumbnails($path, $filename, $ext)
    {
        if (!is_array($this->ThumbnailSize)) {
            return false;
        }

        // Validate all Sizes
        foreach ($this->ThumbnailSize as $key => $Size) {
            if (!isset($Size['width']) || !isset($Size['height']) || !is_numeric($Size['width']) || !is_numeric($Size['height'])) {
                $this->SetError('Configuration Error : One of desire thumbnail size data is incorrect.');

                return false;
            }
        }
        foreach ($this->ThumbnailSize as $key => $Size) {
            try {
                $newSize = $this->GetResizedImage($path, $Size['width'], $Size['height']);
                $newFile = $filename . 'w' . $Size['width'] . '.h' . $Size['height'] . '.' . $ext;
                $this->DoResize($path, $newSize['width'], $newSize['height'], $newFile);

                if (file_exists($GLOBALS['AppConfig']['cache'] . DIRECTORY_SEPARATOR . $newFile)) {
                    $this->FileSaver->Copy($GLOBALS['AppConfig']['cache'] . DIRECTORY_SEPARATOR . $newFile,  $this->FullPath,  $newFile);
                }

                $this->ThumbnailCollection['w' . $Size['width'] . '.h' . $Size['height']] = $filename . 'w' . $Size['width'] . '.h' . $Size['height'] . '.' . $ext;
            } catch (\Exception $e) {
                $this->SetError('Unable to generate thumbnail. Caught Exception :' . $e->getMessage());
            }
        }
    }

    public function ResetThumbnails(int $imageid)
    {
        ImageFile::DeleteThumbnails($imageid);

        $rowImage = $GLOBALS['db']->ExecuteScalarRow('Select * from ' . $GLOBALS['Tables']['images'] . " where imageid={$imageid} limit 1");
        $this->FileSaver->ProcessFile((int)$rowImage['imageid'], $this);
        $GLOBALS['db']->Execute('update ' . $GLOBALS['Tables']['images'] . " set thumbnailfile='" . json_encode($this->ThumbnailCollection) . "' where imageid=" . $rowImage['imageid']);
    }

    public static function DeleteThumbnails(int $imageid)
    {
        $imagelist = $GLOBALS['db']->ExecuteScalarRow('Select * from ' . $GLOBALS['Tables']['images'] . " where imageid={$imageid} limit 1");
        $i = new ImageFile();
        $folder = $i->FindFolder($imageid);
        if ('' != $imagelist['thumbnailfile']) {
            $thumbnail = json_decode($imagelist['thumbnailfile'], true);
            if (is_array($thumbnail)) {
                foreach ($thumbnail as $key => $size) {
                    $image = $i->Path . "/{$folder}/" . $size;
                    if (\file_exists($image)) {
                        @unlink($image);
                    }
                }
            }
        }
    }

    public static function CreateImage($imageType, $path, &$img)
    {
        $imageOk = true;

        switch ($imageType) {
            case 1:
                $img = \imagecreatefromgif($path);

                break;

            case 2:
                $img = \imagecreatefromjpeg($path);

                break;

            case 3:
                $img = \imagecreatefrompng($path);

                break;

            default:
                $imageOk = false;

                break;
        }

        return $imageOk;
    }

    // @desc : Get the Size to be used for resize function.
    public function GetResizedImage($path, $desirewidth, $desireheight)
    {
        $returnSize = [];
        $returnSize['width'] = $desirewidth;
        $returnSize['height'] = $desireheight;
        // Get Image size info
        if (!\file_exists($path)) {
            throw new \Exception('Image path not found');
        }
        [$width_orig, $height_orig, $image_type] = \getimagesize($path);
        $imageOk = true;

        $im = null;
        $imageOk = static::CreateImage($image_type, $path, $im);

        /**
         * * calculate the aspect ratio **.
         */
        $aspect_ratio = (float) $height_orig / $width_orig;
        $desireRatio = (float) $desireheight / $desirewidth;

        if ($aspect_ratio > $desireRatio) { // Image has bigger height
            if ($height_orig <= $desireheight) { // Height is big then width, but still in our desire length
                // Do nothing
            } else {
                $returnSize['width'] = 0;
            }
        } else { // width of image is more than height
            if ($width_orig <= $desirewidth) { // Height is big then width, but still in our desire length
                // Do nothing
            } else {
                $returnSize['height'] = 0;
            }
        }

        return $returnSize;
    }

    /**
     * Create Image URL to use for different size then original.
     *
     * @param [type] $path
     * @param [type] $currenturl
     * @param [type] $desirewidth
     * @param [type] $desireheight
     * @param [type] $noImage
     * @param [type] $resizeScript
     */
    public static function GetResizedImageURL($path, $currenturl, $desirewidth, $desireheight, $noImage, $resizeScript = '')
    {
        // Get Image size info
        if (empty($resizeScript)) {
            $resizeScript = $GLOBALS['AppConfig']['HomeURL'] . '/resize.php';
        }

        if (!\file_exists($path)) {
            return $noImage;
        }
        [$width_orig, $height_orig, $image_type] = @\getimagesize($path);

        $imageOk = true;
        $im = null;
        $imageOk = static::CreateImage($image_type, $path, $im);

        /**
         * * calculate the aspect ratio **.
         */
        $aspect_ratio = (float) $height_orig / $width_orig;
        $desireRatio = (float) $desireheight / $desirewidth;
        $secreturl = 'img-' . substr(md5(base64_encode($path)), 0, 8);
        $_SESSION[$secreturl] = $path;
        if ((float) $aspect_ratio == (float) $desireRatio) {
            // image has perfect ratio, do we need resize?
            if ($width_orig == $desirewidth) {
                return $currenturl;
            }
            if ($width_orig > $desirewidth) {
                return $resizeScript . "?width={$desirewidth}&path=" . $secreturl;
            }

            return $currenturl;
        }
        if ($aspect_ratio > $desireRatio) { // Image has bigger height
            if ($height_orig <= $desireheight) { // Height is big then width, but still in our desire length
                return $currenturl;
            }

            return $resizeScript . "?height={$desireheight}&path=" . $secreturl;
        }
        // width of image is more than height
        if ($width_orig <= $desirewidth) { // Height is big then width, but still in our desire length
            return $currenturl;
        }

        return $resizeScript . "?width={$desirewidth}&path=" . $secreturl;
    }

    public function DoResize($img, $thumb_width = 0, $thumb_height = 0, $filename = 'newimage.jpg')
    {
        // Check if GD extension is loaded
        if (!\extension_loaded('gd') && !\extension_loaded('gd2')) {
            \trigger_error('GD is not loaded', E_USER_WARNING);

            return false;
        }
        if (!\file_exists($img)) {
            return false;
        }

        // Get Image size info
        [$width_orig, $height_orig, $image_type] = \getimagesize($img);

        $imageOk = true;
        $im = null;
        $imageOk = static::CreateImage($image_type, $img, $im);
        if (!$imageOk) {
            trigger_error('Unsupported File Type', E_USER_WARNING);
        }

        if ($thumb_width > 0 && 0 == $thumb_height) {
            $aspect_ratio = (float) $height_orig / $width_orig;
            $thumb_height = round($thumb_width * $aspect_ratio);
        } elseif (0 == $thumb_width && $thumb_height >= 0) {
            $aspect_ratio = (float) $width_orig / $height_orig;
            $thumb_width = round($thumb_height * $aspect_ratio);
        } elseif ($thumb_width >= 0 && $thumb_height >= 0) { // do nothing
        } else {
            $thumb_width = $thumb_height = 200;
        }

        $newImg = imagecreatetruecolor($thumb_width, $thumb_height);

        // Check if this image is PNG or GIF, then set if Transparent
        if ((1 == $image_type) or (3 == $image_type)) {
            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
            $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
            imagefilledrectangle($newImg, 0, 0, $thumb_width, $thumb_height, $transparent);
        }
        imagecopyresampled($newImg, $im, 0, 0, 0, 0, $thumb_width, $thumb_height, $width_orig, $height_orig);

        if (empty($filename)) {
            switch ($image_type) {
                case 1:
                    header('Content-Type: image/gif');
                    imagegif($newImg);

                    break;

                case 2:
                    header('Content-Type: image/jpeg');
                    imagejpeg($newImg);

                    break;

                case 3:
                    header('Content-Type: image/png');
                    imagepng($newImg);

                    break;
            }
        } else {
            switch ($image_type) {
                case 1:
                    imagegif($newImg, $GLOBALS['AppConfig']['cache'] . DIRECTORY_SEPARATOR . $filename);

                    break;

                case 2:
                    imagejpeg($newImg, $GLOBALS['AppConfig']['cache'] . DIRECTORY_SEPARATOR . $filename);

                    break;

                case 3:
                    imagepng($newImg, $GLOBALS['AppConfig']['cache'] . DIRECTORY_SEPARATOR . $filename);

                    break;
            }
        }
        imagedestroy($newImg);
    }

    /**
     * Generates a HTML Grid for Images on given linkertype.
     *
     * @param string $linkertype
     * @param array  $filters
     * @param array  $parameters
     *
     * @return string
     */
    public static function ImageGrid($linkertype = 'cms', $filters = [], $parameters = [])
    {
        $imageFile = new ImageFile();
        $imageFile->ThumbnailSize = $GLOBALS['ThumbnailSize'];

        $options = Grid::DefaultOptions();
        $options['gridurl'] = $parameters['gridpage'];
        $options['gridid'] = $parameters['gridid'] ?? 'mygrid';
        $options['tagname'] = $parameters['tagname'] ?? 'grid';
        $options['pagesize'] = $parameters['pagesize'] ?? '50';
        $options['allowsorting'] = $parameters['allowsorting'] ?? true;
        $options['allowpaging'] = $parameters['allowpaging'] ?? true;
        $options['showtotalrecord'] = $parameters['showtotalrecord'] ?? true;
        $options['totalrecordtext'] = $parameters['totalrecordtext'] ?? '{totalrecord} Images';
        $options['allowselection'] = $parameters['allowselection'] ?? false;
        $options['roworder'] = $parameters['roworder'] ?? false;
        $options['norecordtext'] = $parameters['norecordtext'] ?? 'No image found.';
        $options['rowconditioncallback'] = $parameters['rowconditioncallback'] ?? [];
        $options['dateformat'] = $parameters['dateformat'] ?? 'm/d/Y';
        $options['datetimeformat'] = $parameters['datetimeformat'] ?? 'm/d/Y H:i:a';

        $options['fields'] = $parameters['fields'] ?? [
            'name' => 'ID #',
            'imageid' => [
                'type' => 'numeric',
            ],
            'imagefile' => [
                'name' => 'Image',
                'type' => 'callback',
                'function' => [
                    '\TAS\Core\ImageFile',
                    'CallBackImageUrl',
                ],
            ],
            'tag' => [
                'name' => 'Tag',
                'type' => 'string',
            ],
        ];

        if (isset($parameters['delete'])) {
            $options['option']['delete'] = [
                'link' => $parameters['delete'],
                'iconclass' => 'fa-trash',
                'tooltip' => 'delete this image',
                'tagname' => 'delete btn-outline-danger',
                'paramname' => 'delete',
            ];
        }

        $queryoptions = Grid::DefaultQueryOptions();
        $queryoptions['basicquery'] = 'select * from ' . $GLOBALS['Tables']['images'];
        $queryoptions['pagingquery'] = 'select count(*) from ' . $GLOBALS['Tables']['images'];

        $filter = [];
        $filter[] = " linkertype='" . $linkertype . "'";

        if (is_array($filters) && count($filters) > 0) {
            $filter = array_merge($filter, $filters);
        }

        if (count($filter) > 0) {
            $queryoptions['whereconditions'] = ' where ' . implode(' and ', $filter) . ' ';
        } else {
            $queryoptions['whereconditions'] = ' ';
        }

        $queryoptions['defaultorderby'] = $parameters['defaultorderby'] ?? 'imageid';
        $queryoptions['defaultsortdirection'] = $parameters['defaultsortdirection'] ?? 'asc';
        $queryoptions['indexfield'] = 'imageid';
        $queryoptions['recordshowlimit'] = $parameters['recordshowlimit'] ?? 0;
        $queryoptions['tablename'] = $GLOBALS['Tables']['images'];

        $grid = new Grid($options, $queryoptions);

        return $grid->Render();
    }

    /**
     * Call Back function to create Image on the fly from Thumbnail.
     *
     * @param unknown $row
     * @param unknown $field
     */
    public static function CallBackImageUrl($row, $field)
    {
        if (isset($row['thumbnailfile'])) {
            $thumbs = json_decode($row['thumbnailfile'], true);
            $foldercount = floor($row['imageid'] / UserFile::$MAX_FILE_PER_FOLDER);
            if (isset($thumbs['w120.h90'])) {
                return '<img src="' . $GLOBALS['AppConfig']['UploadURL'] . '/image/' . $foldercount . '/' . $thumbs['w120.h90'] . '" class="thumbnail"/>';
            }

            return '<img src="' . $GLOBALS['AppConfig']['HomeURL'] . '/resize/' . $row['imagefile'] . '?id=' . $row['imageid'] . '&w=120&h=90&crop=true" class="thumbnail"/>';
        }

        return '';
    }
}
