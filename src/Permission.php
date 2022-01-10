<?php

namespace TAS\Core;

class Permission
{
    public $permissions;
    public $usertype;
    public $modules;
    public $action;

    public function __construct()
    {
        $this->permissions = [];
    }

    public function Reload()
    {
        $this->init();
    }

    public function CheckOperationPermission($module, $operation, $userlevel)
    {
        $this->init();

        return $this->permissions[strtolower($userlevel)][strtolower($module)][strtolower($operation)];
    }

    public function CheckModulePermission($module, $userlevel)
    {
        return $this->CheckOperationPermission($module, 'access', $userlevel);
    }

    private function init()
    {
        $rsUserRole = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['userrole'].' order by rolename ');
        if (\TAS\Core\DB::Count($rsUserRole) > 0) {
            while ($row = $GLOBALS['db']->Fetch($rsUserRole)) {
                $roles = json_decode($row['permission'], true);
                foreach ($this->modules as $mkey => $mval) {
                    foreach ($this->action as $akey => $aval) {
                        if (isset($roles[$mkey][$akey])) {
                            $this->permissions[$row['userroleid']][$mkey][$akey] = (true == $roles[$mkey][$akey]) ? true : false;
                        } else {
                            $this->permissions[$row['userroleid']][$mkey][$akey] = false;
                        }
                    }
                }
            }
        } else {
            throw new \Exception('Configuration error, Please check your site setup');
        }
    }
}
