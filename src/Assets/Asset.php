<?php

namespace TAS\Core\Assets;

use TAS\Core\Entity;

/**
 * Defines the Assets information.
 */
class Asset extends Entity
{
    public int $AssetID;
    public string $AssetType;
    public string $Filename;
    public string $URL;
    public string $PhysicalPath;
    public string $ExtraInfo;
    public int $LinkerID;
    public string $LinkerType;
    public string $FolderID;
    public string $Caption;

    public string $ContentType;
    public int $ContentLength;

    public function __construct(int $_assetID = 0)
    {
        if ($_assetID > 0) {
            $this->AssetID = $_assetID;
            $this->Load($this->AssetID);
        }
    }

    public function Load(int $id)
    {
        if (!is_numeric($id) || (int) $id < 0) {
            return false;
        }
        $this->_isloaded = false;
        $rs = $GLOBALS['db']->Execute('Select * from ' . $this->_tablename . ' where assetid=' . (int) $id);
        if ($rs && \TAS\Core\DB::Count($rs) > 0) {
            $this->LoadFromDB($rs);

            return true;
        }

        return false;
    }

}
