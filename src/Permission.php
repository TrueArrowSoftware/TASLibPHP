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
        return $this->permissions[strtolower($userlevel)][strtolower($module)][strtolower($operation)] ?? false;
    }

    public function CheckModulePermission($module, $userlevel)
    {
        return $this->CheckOperationPermission($module, 'access', $userlevel);
    }

    private function init()
    {
        $rsUserRole = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['userrole'].' order by rolename ');
        if (\TAS\Core\DB::Count($rsUserRole) > 0) {
            foreach ($rsUserRole as $row) {
                $roles = json_decode($row['permission'], true);

                $userroleid = $row['userroleid'];
                $this->permissions = [];
                $permissions = &$this->permissions[$userroleid];

                foreach ($this->modules as $mkey => $mval) {
                    $permissions[$mkey] = [];
                    $module_permissions = &$permissions[$mkey];

                    foreach ($this->action as $akey => $aval) {
                        $module_permissions[$akey] = isset($roles[$mkey][$akey]) && $roles[$mkey][$akey];
                    }
                }
            }
        } else {
            throw new \Exception('Configuration error, Please check your site setup');
        }
    }
}
