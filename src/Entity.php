<?php

namespace TAS\Core;

/**
 * Base class for all Entities in project. This Class handles the error and common operations.
 */
class Entity
{
    protected $_isloaded;
    protected $_tablename;
    protected $_primarykey;

    public static $Errors;

    public function __construct()
    {
        self::$Errors = [];
        $this->_isloaded = false;
        $this->_tablename = '';
        $this->_primarykey = 0;
    }

    /**
     * ReadOnly function, Tells if object is loaded from DB yet.
     */
    public function IsLoaded()
    {
        return $this->_isloaded;
    }

    public static function SetError($error, $level = 0)
    {
        if ($error == '') {
            return false;
        }
        self::$Errors[] = [
            'message' => $error,
            'level' => $level,
        ];
    }

    public static function GetError()
    {
        return self::$Errors[count(self::$Errors) - 1];
    }

    public static function GetErrors()
    {
        return self::$Errors;
    }

    /**
     * Return the Json Object from `this` operator.
     */
    public function ToJson()
    {
        return json_encode($this);
    }

    public function ObjectAsArray()
    {
        $t = $this->ToJson();
        $t = json_decode($t, true);

        return $t;
    }

    public function EmailKeywords()
    {
        $array = $this->ObjectAsArray();
        $a = \TAS\Core\Utility::SinglizeArray($array);
        $array = [];
        foreach ($a as $i => $k) {
            $array[strtolower($i)] = $k;
        }

        return $array;
    }

    public static function GetTableName()
    {
        $o = get_called_class();
        $obj = new $o();

        return $obj->_tablename;
    }

    /*
     * Validate the Form input against the field information.
     * @param $fields array Field information, output of GetFields() function call.
     * @param $values array Values capture from input form.
     */
    public static function InputValidate($fields, $values)
    {
        $isvalid = true;
        foreach ($fields as $fieldname => $fieldinfo) {
            if (isset($fieldinfo['required']) && $fieldinfo['required'] == true) {
                if (isset($values[$fieldname]) && ($values[$fieldname] == null || $values[$fieldname] == '')) {
                    $isvalid = false;
                    self::SetError($fieldinfo['label'].' is required');
                } elseif (is_array($values[$fieldname]) && count($values[$fieldname]) == 0) {
                    $isvalid = false;
                    self::SetError($fieldinfo['label'].' is required');
                }
            }
            if (!empty($values[$fieldname])) {
                switch ($fieldinfo['type']) {
                    case 'email':
                        if (!\TAS\Core\DataValidate::ValidateEmail($values[$fieldname])) {
                            $isvalid = false;
                            self::SetError($fieldinfo['label'].' is not a valid email');
                        }
                        break;

                    case 'url':
                        if (!\TAS\Core\DataValidate::ValidateURL($values[$fieldname])) {
                            $isvalid = false;
                            self::SetError($fieldinfo['label'].' is not a valid url');
                        }
                        break;

                    case 'date':
                        if (!\TAS\Core\DataValidate::IsDate($values[$fieldname])) {
                            $isvalid = false;
                            self::SetError($fieldinfo['label'].' is not a valid date');
                        }
                        break;
                }
            }
        }

        return $isvalid;
    }

    public static function Validate($values, $tablename)
    {
        if (!is_array($values)) {
            return false;
        }

        $tableinfo = \TAS\Core\DB::GetTableInformation($tablename);
        foreach ($values as $i => $v) {
            try {
                if (isset($tableinfo[$i])) {
                    switch ($tableinfo[$i]['type']) {
                    case 'int':
                        if (strtolower($tableinfo[$i]['Null']) == 'yes') {
                            if ($v != '' && !is_numeric($v)) {
                                self::SetError("For $i, $v is not numeric", 10);

                                return false;
                            }
                        } elseif (!is_numeric($v)) {
                            self::SetError("For $i, $v is not numeric", 10);

                            return false;
                        }
                        break;
                    case 'float':
                        if (strtolower($tableinfo[$i]['Null']) == 'yes') {
                            if ($v != '' && !is_numeric($v)) {
                                self::SetError("For $i, $v is not numeric", 10);

                                return false;
                            }
                        } elseif (!is_numeric($v)) {
                            self::SetError("For $i, $v is not numeric", 10);

                            return false;
                        } else {
                            $v1 = floatval($v);
                            if ($v1 != $v) {
                                self::SetError("For $i,  $v is not numeric", 10);

                                return false;
                            }
                        }
                        break;
                    case 'date':
                        if (strtolower($tableinfo[$i]['Null']) == 'yes') {
                            if ($v != '' && !\TAS\Core\DataValidate::IsDate($v)) {
                                self::SetError("For $i, $v is not a date", 10);

                                return false;
                            }
                        } else {
                            if ($v == '' || !\TAS\Core\DataValidate::IsDate($v)) {
                                self::SetError("For $i, $v is not a date", 10);

                                return false;
                            }
                        }
                        break;
                    case 'string':
                        if (isset($tableinfo[$i]['size']) && $tableinfo[$i]['size'] > 0) {
                            if (strlen($v) > $tableinfo[$i]['size']) {
                                self::SetError("For $i, $v exceed size limit", 10);

                                return false;
                            }
                        }
                        break;
                    default: break;
                }
                }
            } catch (\Exception $ex) {
                self::SetError("For $i,  $v generate Exception. ".$ex->getMessage(), 10);

                return false;
            }
        }

        return true;
    }

    /**
     * Generic Load of object from database recordset using column name been same as object properties.
     *
     * @param recordset/object $rs
     */
    public function LoadFromRecordSet($rs)
    {
        $row = $GLOBALS['db']->Fetch($rs);
        foreach ($this as $key => $value) {
            if (isset($row[strtolower($key)])) {
                $this->{$key} = \mb_convert_encoding($row[strtolower($key)], 'utf-8');
            }
        }
        $this->_isloaded = true;
    }

    public static function GetFieldsGeneric($tablename = '', $param = [])
    {
        $tableinfo = \TAS\Core\DB::GetTableInformation($tablename);

        $fields = [];
        $ctr = (isset($param['startcounter']) ? $param['startcounter'] : 0);
        foreach ($tableinfo as $k => $v) {
            $fields[$k] = [
                'field' => $k,
                'id' => $k,
                'type' => 'varchar',
                'displayorder' => $ctr++,
                'value' => '',
                'size' => '30',
                'group' => (isset($param['group']) ? $param['group'] : 'basic'),
                'label' => ucwords(str_replace('_', ' ', $k)),
            ];

            switch ($v['type']) {
                case 'varchar':
                case 'string':
                    $fields[$k]['type'] = 'varchar';
                    $fields[$k]['maxlength'] = (($v['size'] > 0) ? $v['size'] : null);
                    break;
                case 'int':
                    $fields[$k]['type'] = 'numeric';
                    $fields[$k]['size'] = 10;
                    break;
                case 'float':
                    $fields[$k]['type'] = 'numeric';
                    $fields[$k]['size'] = 15;
                    break;
                case 'text':
                    $fields[$k]['type'] = 'text';
                    break;
                case 'date':
                    $fields[$k]['type'] = 'datetime';
                    $fields[$k]['size'] = 20;
                    break;
            }
            if ($v['Null'] == 'NO') {
                $field[$k]['required'] = true;
            }
        }

        return $fields;
    }

    public static function ValidateAgainstTable($postdata = [], $table, $callback = null)
    {
        $message = [];
        if ($callback != null) {
            $tableinfo = call_user_func($callback);
        } else {
            $tableinfo = self::GetFieldsGeneric($table);
        }
        $tableinfo = $tableinfo['Fields'];
        foreach ($postdata as $k => $v) {
            if (!is_array($v)) {
                $v = \TAS\Core\DataFormat::DoSecure($v);
                if (isset($tableinfo[$k]) && isset($tableinfo[$k]['required'])) {
                    if ($tableinfo[$k]['required'] == true) {
                        if ($v == '') {
                            $message[] = [
                                'level' => 10,
                                'message' => $tableinfo[$k]['label'].' is required field.',
                            ];
                        }
                    }
                    if ($tableinfo[$k]['type'] == 'numeric') {
                        if (($v != '') && !is_numeric($v)) {
                            $message[] = [
                                'level' => 10,
                                'message' => $tableinfo[$k]['label'].' is not a number.',
                            ];
                        }
                    }
                }
            }
        }

        return $message;
    }

    public static function ParsePostToArray($fields)
    {
        $obj = new \TAS\Core\DataFormat();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $d = [];
            foreach ($fields as $field) {
                if ($field['type'] == 'readonly') {
                    continue;
                }
                switch ($field['type']) {
                    case 'checkbox':
                        $d[$field['id']] = isset($_POST[$field['id']]) ? 1 : 0;
                        break;
                    case 'text':
                        $d[$field['id']] = $obj->DBString($_POST[$field['id']]);
                        break;
                    case 'numeric':
                        $d[$field['id']] = floatval(\TAS\Core\DataFormat::DoSecure($_POST[$field['id']]));
                        break;
                    case 'date':
                        $d[$field['id']] = \TAS\Core\DataFormat::DateToDBFormat(\TAS\Core\DataFormat::DoSecure($_POST[$field['id']]));
                        break;
                    case 'cb':
                        break; // do nothing.
                    case 'select':
                        if (!isset($field['multiple']) || $field['multiple'] == false) {
                            $d[$field['id']] = \TAS\Core\DataFormat::DoSecure($_POST[$field['id']]);
                        } else {
                            foreach ($_POST[$field['id']] as $i => $val) {
                                $_POST[$field['id']][$i] = \TAS\Core\DataFormat::DoSecure($val);
                            }
                        }
                        break;
                    default:
                        if (isset($_POST[$field['id']])) {
                            $d[$field['id']] = \TAS\Core\DataFormat::DoSecure($_POST[$field['id']]);
                        }
                        break;
                }
            }

            return $d;
        } else {
            return [];
        }
    }
}
