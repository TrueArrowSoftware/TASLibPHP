<?php
/**
 * Basic Site Configuration file.
 */
@session_start();
@ob_start();
date_default_timezone_set('Asia/Kolkata');

$GLOBALS['AppConfig']['PhysicalPath'] = dirname(__FILE__);
require_once $GLOBALS['AppConfig']['PhysicalPath'].'/vendor/autoload.php';

spl_autoload_register(array(
    '\\TAS\\Core\\Utility',
    'AutoLoad',
), true);

if (file_exists($GLOBALS['AppConfig']['PhysicalPath'].'/configure.local.php')) {
    require_once $GLOBALS['AppConfig']['PhysicalPath'].'/configure.local.php';
} else {
    define('HOST', 'localhost');
    define('LOCAL_USER', 'root');
    define('LOCAL_PASSWORD', '');
    define('LOCAL_DB', 'testdb');
    define('URL_FOLDERPATH', '/');
    define('ADMIN_EMAIL', 'youremail@example.com');
}

$GLOBALS['db'] = new TAS\Core\DB(HOST, LOCAL_USER, LOCAL_PASSWORD, LOCAL_DB);
$GLOBALS['AppConfig']['folderpath'] = URL_FOLDERPATH;
$GLOBALS['AppConfig']['AdminMail'] = ADMIN_EMAIL;

$GLOBALS['AppConfig']['SiteName'] = 'Project Name';
$GLOBALS['AppConfig']['LegalName'] = 'Project Legal Name';
$GLOBALS['AppConfig']['Domain'] = 'example.com';

$domain = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
$GLOBALS['AppConfig']['NonSecureURL'] = 'http://'.$domain.$GLOBALS['AppConfig']['folderpath'];
$GLOBALS['AppConfig']['SecureURL'] = 'http://'.$domain.$GLOBALS['AppConfig']['folderpath'];
$GLOBALS['AppConfig']['HomeURL'] = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? $GLOBALS['AppConfig']['NonSecureURL'] : $GLOBALS['AppConfig']['SecureURL'];
$GLOBALS['AppConfig']['Language'] = 'en_US'; // use english for windows and en_US for linux based server.
$GLOBALS['AppConfig']['ImageURL'] = $GLOBALS['AppConfig']['HomeURL'].'/images';

$GLOBALS['AppConfig']['UploadPath'] = $GLOBALS['AppConfig']['PhysicalPath'].'/assets';
$GLOBALS['AppConfig']['UserFileURL'] = $GLOBALS['AppConfig']['HomeURL'].'/assets';
$GLOBALS['AppConfig']['UploadURL'] = $GLOBALS['AppConfig']['UserFileURL'];
$GLOBALS['AppConfig']['TemplatePath'] = $GLOBALS['AppConfig']['PhysicalPath'].'/theme/template';
$GLOBALS['AppConfig']['cache'] = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache';

$GLOBALS['AppConfig']['Currency'] = '&euro;';

$GLOBALS['AppConfig']['UseSMTPAuth'] = false;
$GLOBALS['AppConfig']['SMTPServer'] = 'smtp.sendgrid.net';
$GLOBALS['AppConfig']['SMTPUsername'] = 'apikey';
$GLOBALS['AppConfig']['SMTPPassword'] = 'somepassword key from hosting';
$GLOBALS['AppConfig']['SMTPServerPort'] = 587;
$GLOBALS['AppConfig']['SMTP-TLS'] = true;

$GLOBALS['AppConfig']['AdminURL'] = $GLOBALS['AppConfig']['HomeURL'].'/admin/';
$GLOBALS['AppConfig']['AdminTemplate'] = $GLOBALS['AppConfig']['PhysicalPath'].'/theme/template';
$GLOBALS['AppConfig']['SenderEmail'] = 'noreply@'.$GLOBALS['AppConfig']['Domain'];

$GLOBALS['AppConfig']['DeveloperMode'] = true;
$GLOBALS['AppConfig']['DebugMode'] = false; // Can be true or false only
$GLOBALS['AppConfig']['PageSize'] = 50;

$GLOBALS['AppConfig']['NoImage_Listing'] = $GLOBALS['AppConfig']['HomeURL'].'/theme/images/noimage.png';

if ($GLOBALS['AppConfig']['DeveloperMode']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
}

// Language file loading
putenv('LANG='.$GLOBALS['AppConfig']['Language']);
setlocale(LC_COLLATE, $GLOBALS['AppConfig']['Language']); // LC_ALL only if you want currency and number format as well
bindtextdomain('lang', $GLOBALS['AppConfig']['PhysicalPath'].'/languages');
bind_textdomain_codeset('lang', 'UTF-8');

set_include_path(get_include_path().PATH_SEPARATOR.$GLOBALS['AppConfig']['PhysicalPath']);

$GLOBALS['db']->Debug = $GLOBALS['AppConfig']['DebugMode'];

if (!$GLOBALS['db']->Connect()) {
    Redirect($GLOBALS['AppConfig']['HomeURL'].'/dbfail.html');
}

$rs = $GLOBALS['db']->Execute('Select * From '.$GLOBALS['Tables']['enumeration'].'  order by type, displayorder ASC');

if ($GLOBALS['db']->RowCount($rs) > 0) {
    while ($row = $GLOBALS['db']->FetchArray($rs)) {
        $GLOBALS[$row['type']][$row['ekey']] = $row['value'];
        if ($GLOBALS['AppConfig']['DeveloperMode'] == true) {
            $GLOBALS['types'][$row['type']][$row['ekey']] = $row['value'];
        }
    }
}

$GLOBALS['Configuration'] = array();
$rsConfiguration = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['configuration'].' order by displayname');
if (\TAS\DB::Count($rsConfiguration) > 0) {
    while ($rowConfiguration = $GLOBALS['db']->Fetch($rsConfiguration)) {
        $GLOBALS['Configuration'][$rowConfiguration['settingkey']] = $rowConfiguration['settingvalue'];
    }
}
$GLOBALS['module'] = array();
$rsModule = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['module'].'');
if (\TAS\DB::Count($rsModule) > 0) {
    while ($rsModuleGet = $GLOBALS['db']->Fetch($rsModule)) {
        $GLOBALS['module'][$rsModuleGet['slug']] = $rsModuleGet['modulename'];
    }
}

$rsUserRole = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['userrole'].' order by rolename ');
if (\TAS\DB::Count($rsUserRole) > 0) {
    while ($rowset = $GLOBALS['db']->Fetch($rsUserRole)) {
        $roles = json_decode($rowset['permission'], true);
        foreach ($GLOBALS['module'] as $mkey => $mval) {
            foreach ($GLOBALS['action'] as $akey => $aval) {
                if (isset($roles[$mkey][$akey])) {
                    $GLOBALS['permissions'][$rowset['userroleid']][$mkey][$akey] = $roles[$mkey][$akey];
                } else {
                    $GLOBALS['permissions'][$rowset['userroleid']][$mkey][$akey] = false;
                }
            }
        }
    }
}

$permission = new \TAS\Core\Permission();
$permission->usertype = $GLOBALS['db']->FirstColumnArray('select userroleid from '.$GLOBALS['Tables']['userrole'].'');
$permission->modules = $GLOBALS['module'];
$permission->action = $GLOBALS['action'];
$permission->permissions = $GLOBALS['permissions'];
$permission->Reload();
$GLOBALS['ThumbnailSize'] = array(
    0 => array(
        'width' => 550,
        'height' => 415,
    ),
    1 => array(
        'width' => 120,
        'height' => 90,
    ),
    2 => array(
        'width' => 370,
        'height' => 220,
    ),
    3 => array(
        'width' => 100,
        'height' => 65,
    ),
    4 => array(
        'width' => 900,
        'height' => 500,
    ),
);
