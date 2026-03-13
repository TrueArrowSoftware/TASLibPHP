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

    /**
     * @return void
     */
    public function Reload()
    {
        $this->init();
    }

    /**
     * @param string $module
     * @param string $operation
     * @param int|string $userlevel
     * @return bool|mixed
     */
    public function CheckOperationPermission($module, $operation, $userlevel)
    {
        return $this->permissions[strtolower($userlevel)][strtolower($module)][strtolower($operation)] ?? false;
    }

    /**
     * @param string $module
     * @param int|string $userlevel
     * @return bool|mixed
     */
    public function CheckModulePermission($module, $userlevel)
    {
        return $this->CheckOperationPermission($module, 'access', $userlevel);
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function init()
    {
        $rsUserRole = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['userrole'].' order by rolename');
        if (\TAS\Core\DB::Count($rsUserRole) > 0) {
            $this->permissions = [];
            foreach ($rsUserRole as $row) {
                $roles = json_decode($row['permission'], true);
                $userroleid = $row['userroleid'];
                $permissions = &$this->permissions[$userroleid];

                foreach ($this->modules as $mkey => $mval) {
                    $permissions[$mkey] = [];
                    $module_permissions = &$permissions[$mkey];

                    foreach ($this->action as $akey => $aval) {
                        $module_permissions[$akey] = $roles[$mkey][$akey] ?? 0;
                    }
                }
            }
        } else {
            throw new \Exception('Configuration error, Please check your site setup');
        }
    }
}
