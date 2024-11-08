<?php

namespace TAS\Core\Assets;

use TAS\Core\DB;
use TAS\Core\Entity;

class AssetsFolder extends Entity
{
    public int $FolderID;
    public string $FolderName;
    public int $ParentFolderID;
    public int $ProjectID;
    public string $AssetsType;
    public string $BreadCrumb;
    public string $Info;
    public \DateTime $CreatedDate;
    public ?\DateTime $ModifiedDate = null;

    protected DB $_db;

    public function __construct(DB $db, string $tablename, int $folderID = 0)
    {
        $this->_db = $db;

        $this->_tablename = $tablename;
        $this->_isloaded = false;

        $this->init();
        $this->FolderID = $folderID;

        if (is_numeric($this->FolderID) && $this->FolderID  > 0) {
            $this->Load();
        }
    }

    private function init()
    {
        $this->FolderID = 0;
        $this->FolderName = "";
        $this->ParentFolderID = 0;
        $this->ProjectID = 0;
        $this->AssetsType = "";
        $this->BreadCrumb = "";
        $this->CreatedDate = new \DateTime();
        $this->ModifiedDate = null;
    }

    public function Load($id = 0)
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            if ($this->FolderID > 0) {
                $id = $this->FolderID;
            } else {
                return false;
            }
        }
        $rs = $this->_db->Execute('Select * from ' . $this->_tablename . ' where folderid=' . (int) $id . ' limit 1');
        if (DB::Count($rs) > 0) {
            $this->LoadFromRecordSet($rs);
        }
    }

    public function Add($values = [])
    { 
        if (!self::Validate($values, $this->_tablename)) { 
            return false;
        }

        if ($this->_db->Insert($this->_tablename, $values)) {
            $_id = $this->_db->GeneratedID(); 
            if ($_id > 0) {
                
                $this->_db->Execute("UPDATE assetsfolder AS af
                LEFT JOIN assetsfolder AS parent ON af.parentfolderid = parent.folderid
                SET af.breadcrumb = 
                    CASE 
                        WHEN parent.breadcrumb IS NOT NULL THEN CONCAT(parent.breadcrumb, af.folderid, ',')
                        ELSE CONCAT(',0,', af.folderid, ',')
                    END
                where af.folderid= ". $_id);
                
            }
            return $_id;
        } 
        return false;
    }

    public function Update($values = [])
    {
        if (is_null($values) || !is_array($values) || count($values) <= 0) {
            $tv = json_decode($this->ToJson(), true);
            foreach ($tv as $k => $v) {
                $values[strtolower($k)] = $v;
            }
        }
        if (!self::Validate($values, $this->_tablename) || 0 == $this->FolderID) {
            return false;
        }
        if ($this->_db->Update($this->_tablename, $values, $this->FolderID, 'folderid')) {
            return true;
        }

        return false;
    }

    public function Delete($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            return false;
        }
        $id = floor((int) $id);

        $delete = $this->_db->Execute('Delete from ' . $this->_tablename . ' where folderid=' . (int) $id . ' limit 1');

        return true;
    }


    public function GetFields()
    {
        $fields = Entity::GetFieldsGeneric($this->_tablename);

        $a = $this->ObjectAsArray();
        foreach ($a as $i => $v) {
            if (isset($fields[strtolower($i)])) {
                $fields[strtolower($i)]['value'] = $v;
            }
        }

        unset($fields['folderid']);

        return $fields;
    }
}
