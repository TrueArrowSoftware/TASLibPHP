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
     * @return bool
     */
    public function CheckOperationPermission($module, $operation, $userlevel)
    {
        $granted = $this->permissions[strtolower($userlevel)][strtolower($module)][strtolower($operation)] ?? false;

        // Only a "yes" grants: true/1 or what a checkbox posts ('1', 'on'). Any other truthy value used to grant too,
        // so a stored "false"/"off"/"0 " or a misplaced array opened the operation.
        return true === $granted || 1 === $granted
            || (is_string($granted) && in_array(strtolower(trim($granted)), ['1', 'on', 'yes', 'true'], true));
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
