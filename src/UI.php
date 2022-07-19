<?php

namespace TAS\Core;

class UI
{
    public static function ImageURLFinder($imageObj, $width, $height)
    {
        if (null == $imageObj) {
            return $GLOBALS['AppConfig']['NoImage_Listing'];
        }
        if (is_array($imageObj['thumbnails']) && count($imageObj['thumbnails']) > 0 && isset($imageObj['thumbnails']['w'.$width.'.h'.$height])) {
            return $imageObj['baseurl'].$imageObj['thumbnails']['w'.$width.'.h'.$height];
        }

        return ImageFile::GetResizedImageURL($imageObj['physicalpath'], $imageObj['url'], $width, $height, $GLOBALS['AppConfig']['NoImage_Listing']);
    }

    public static function GetEmailContent($EmailID)
    {
        $rsEmail = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['emailcms'].' where id='.(int) $EmailID);
        if ($GLOBALS['db']->RowCount($rsEmail) > 0) {
            $row = $GLOBALS['db']->Fetch($rsEmail);

            return $row;
        }

        return false;
    }

    /**
     * Convert a give array in HTML Option Tags.
     *
     * @param mixed $sourceArray
     * @param mixed $selectedvalue
     */
    public static function ArrayToDropDown($sourceArray, $selectedvalue)
    {
        if (!is_array($sourceArray)) {
            return '';
        }
        $val = '';
        reset($sourceArray);
        foreach ($sourceArray as $i => $v) {
            if (is_array($selectedvalue)) {
                $select = in_array(\TAS\Core\DataFormat::DoSecure($i), $selectedvalue) ? true : false;
            } else {
                $select = (\TAS\Core\DataFormat::DoSecure($i) == $selectedvalue) ? true : false;
            }
            $val .= '<option value="'.\TAS\Core\DataFormat::DoSecure($i).'"'.($select ? ' selected="selected"' : '').'>'.$v."</option>\r\n";
        }

        return $val;
    }

    /**
     * MultiArray To Dropdown.
     *
     * @param [type] $sourceArray
     * @param string $val
     * @param string $label
     * @param [type] $selectValue
     * @return void
     */
    public static function MultiArrayToDropDown($sourceArray, string $val, string $label, $selectValue)
    {
        if (!is_array($sourceArray)) {
            return '';
        }
        $html ='';
        reset($sourceArray);
        foreach ($sourceArray as $v) {
            if (!is_array($v)) {
                continue;
            }
            if (is_array($selectValue)) {
                $select = in_array(\TAS\Core\DataFormat::DoSecure($v[$val]), $selectValue) ? true : false;
            } else {
                $select = (\TAS\Core\DataFormat::DoSecure($v[$val]) == $selectValue) ? true : false;
            }
            $html .= '<option value="'.\TAS\Core\DataFormat::DoSecure($v[$val]).'"'.($select ? ' selected="selected"' : '').'>'.$v[$label]."</option>\r\n";
        }

        return $html;
    }

    /**
     * function will create the HTML option list from record set.
     *
     * @param mixed $rs
     * @param mixed $selectedvalue
     * @param mixed $indexCol
     * @param mixed $labelCol
     * @param mixed $showSelect
     * @param mixed $SelectText
     */
    public static function RecordSetToDropDown($rs, $selectedvalue, $indexCol, $labelCol, $showSelect = true, $SelectText = 'Select')
    {
        global $db;
        $list = '';
        $SelectionDone = false;
        if ($db->RowCount($rs) > 0) {
            $db->Reset($rs);
            $columns = explode(' ', $labelCol);
            foreach ($rs as $row) {
                if (count($columns) > 1) {
                    reset($columns);
                    $text = '';
                    foreach ($columns as $ckey => $cval) {
                        if (isset($row[$cval])) {
                            $text .= ucwords($row[$cval]).' ';
                        }
                    }
                } else {
                    $text = ucwords($row[$labelCol]);
                }
                if ((is_array($selectedvalue) && in_array($row[$indexCol], $selectedvalue)) || $row[$indexCol] == $selectedvalue) {
                    $list .= '<option value="'.$row[$indexCol].'" selected="selected">'.$text."</option>\n";
                    $SelectionDone = true;
                } else {
                    $list .= '<option value="'.$row[$indexCol].'">'.$text."</option>\n";
                }
            }
        }
        if ($showSelect) {
            if ($SelectionDone) {
                $list = '<option value="">'.$SelectText.'</option>'.$list;
            } else {
                $list = '<option value="" selected="selected">'.$SelectText.'</option>'.$list;
            }
        } else {
            $list = ''.$list;
        }

        return $list;
    }

    /**
     * Create a Option List for DataList HTML tag $labelCol can use space separated column name to combine them.
     *
     * @param mixed $rs
     * @param mixed $labelCol
     */
    public static function RecordSetToDataListOptions($rs, $labelCol)
    {
        $list = '';
        $SelectionDone = false;
        if (\TAS\Core\DB::Count($rs) > 0) {
            \TAS\Core\DB::Reset($rs);
            $columns = explode(' ', $labelCol);
            foreach ($rs as $row) {
                if (count($columns) > 1) {
                    reset($columns);
                    $text = '';
                    foreach ($columns as $ckey => $cval) {
                        if (isset($row[$cval])) {
                            $text .= ucwords($row[$cval]).' ';
                        }
                    }
                } else {
                    $text = ucwords($row[$labelCol]);
                }
                $list .= '<option value="'.$text.'" />';
            }
        }

        return $list;
    }

    /**
     * Create numeric dropdown for given range and steps.
     *
     * @param mixed $start
     * @param mixed $end
     * @param mixed $step
     * @param mixed $selectedvalue
     * @param mixed $showSelect
     * @param mixed $showSelectName
     */
    public static function NumericRangeToDropDown($start, $end, $step, $selectedvalue, $showSelect = true, $showSelectName = 'Select')
    {
        if ($showSelect) {
            $list = '<option value="">'.$showSelectName.'</option>';
        } else {
            $list = '';
        }
        for ($i = $start; $i <= $end; $i = $i + $step) {
            if ($i == $selectedvalue) {
                $list .= '<option value="'.$i.'" selected="selected">'.$i."</option>\n";
            } else {
                $list .= '<option value="'.$i.'">'.$i."</option>\n";
            }
        }

        return $list;
    }

    /**
     * Dropdown option for Months.
     */
    public static function MonthDropDown(string $selectedValue = '')
    {
        $month = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];

        return self::ArrayToDropDown($month, $selectedValue);
    }

    /**
     * Create HTML Checkbox using array.
     *
     * @param mixed $array
     * @param mixed $selectedvalues
     * @param mixed $name
     * @param mixed $type
     * @param mixed $isrequired
     * @param mixed $labelformat
     */
    public static function ArrayToCheckRadioList($array, $selectedvalues, $name = 'list', $type = 'checkbox', $isrequired = false, $labelformat = '{text}')
    {
        $list = '';
        $SelectionDone = false;
        if (!is_array($selectedvalues)) {
            if (is_numeric($selectedvalues)) {
                $selectedvalues = [
                    $selectedvalues,
                ];
            } else {
                $selectedvalues = explode(',', $selectedvalues);
            }
        }
        if (is_array($array) && count($array) > 0) {
            reset($array);
            $list .= '<ul class="rslist">';
            foreach ($array as $i => $v) {
                $text = \TAS\Core\TemplateHandler::PrepareContent($labelformat, [
                    'index' => $i,
                    'text' => $v,
                ]);
                $list .= '<li><label class="'.('radio' == $type ? 'custom-radio' : ('checkbox' == $type ? 'custom-checkbox' : 'customlist')).'">
                          <input type="'.$type.'" class="form-control '.(($isrequired) ? 'required' : '').'" '.(('radio' == $type) ? ' value="'.$i.'"' : ' value="on"').(('radio' == $type) ? ' name="'.$name.'"' : ' name="'.$name.'['.$i.']"').' '.(in_array($i, $selectedvalues) ? 'checked="checked"' : '').'>
                          <span class="'.('radio' == $type ? 'checkmark' : ('checkbox' == $type ? 'checkmark-box' : 'customlist')).'"></span><span class="text-label">'.$text.'</span></label></li>';
            }
            $list .= '</ul>';
        }

        return $list;
    }

    /**
     * Checklist for RecordSet.
     *
     * @param [type] $rs
     * @param [type] $selectedvalues
     * @param [type] $indexCol
     * @param [type] $labelformat
     * @param string $name
     * @param bool   $isrequired
     * @param string $type
     */
    public static function RecordSetToCheckRadioList($rs, $selectedvalues, $indexCol, $labelformat, $name = 'list', $isrequired = false, $type = 'checkbox')
    {
        $list = '';
        $SelectionDone = false;
        if (!is_array($selectedvalues)) {
            if (is_numeric($selectedvalues)) {
                $selectedvalues = [
                    $selectedvalues,
                ];
            } else {
                $selectedvalues = explode(',', $selectedvalues);
            }
        }
        if ($GLOBALS['db']->RowCount($rs) > 0) {
            \TAS\Core\DB::Reset($rs);
            $list .= '<ul class="rslist">';
            foreach ($rs as $row) {
                $text = \TAS\Core\TemplateHandler::PrepareContent($labelformat, $row);
                $list .= '<li><label class="'.('radio' == $type ? 'custom-radio' : ('checkbox' == $type ? 'custom-checkbox' : 'customlist')).'">
                          <input type="'.$type.'" class="form-control '.(($isrequired) ? 'required' : '').'" '.(('radio' == $type) ? ' value="'.$row[$indexCol].'"' : ' value="on"').(('radio' == $type) ? ' name="'.$name.'"' : ' name="'.$name.'['.$row[$indexCol].']"').' '.(in_array($row[$indexCol], $selectedvalues) ? 'checked="checked"' : '').'>
                          <span class="'.('radio' == $type ? 'checkmark' : ('checkbox' == $type ? 'checkmark-box' : 'customlist')).'"></span><span class="text-label">'.$text.'</span></label></li>';
            }
            $list .= '</ul>';
        }

        return $list;
    }

    /**
     * @deprecated 2.0.0
     *
     * To create a grid based on permission, Please use GRID class instead. It will be removed from TASLib 2.0
     *
     * @param [type] $SQLQuery
     * @param [type] $pages
     * @param [type] $tagname
     * @param array $param
     */
    public static function HTMLGridFromRecordSet($SQLQuery, $pages, $tagname, $param = [])
    {
        $options = \TAS\Core\Grid::DefaultOptions();
        $queryoptions = \TAS\Core\Grid::DefaultQueryOptions();

        $queryoptions['basicquery'] = $SQLQuery['basicquery'];
        $queryoptions['defaultorderby'] = ($param['defaultorder'] ?? '');
        $queryoptions['defaultsortdirection'] = ($param['defaultsort'] ?? '');
        $queryoptions['whereconditions'] = $SQLQuery['where'];
        $queryoptions['pagingquery'] = ($SQLQuery['pagingQuery'] ?? '');
        $queryoptions['pagingqueryend'] = ($SQLQuery['pagingQueryEnd'] ?? '');
        $queryoptions['indexfield'] = $param['indexfield'];
        $queryoptions['orderby'] = ($param['orderby'] ?? '');
        $queryoptions['noorderby'] = ($param['noorder'] ?? '');
        $queryoptions['recordshowlimit'] = ($param['showonly'] ?? 0);
        $queryoptions['tablename'] = ($param['tablename'] ?? '');

        $options['gridurl'] = $pages['gridpage'];
        $options['gridid'] = $param['tablename'];
        $options['tagname'] = $tagname;
        $options['pagesize'] = $param['pagesize'] ?? $GLOBALS['AppConfig']['PageSize'];
        $options['allowsorting'] = ($param['allowsort'] ?? true);
        $options['allowpaging'] = (isset($param['nopaging']) ? !$param['nopaging'] : true);
        $options['showtotalrecord'] = true;
        $options['totalrecordtext'] = '{totalrecord} Records';
        $options['allowselection'] = $param['allowselection'];
        $options['roworder'] = ($param['AllowRowSort'] ?? false);
        $options['fields'] = $param['fields'];

        $options['rowconditioncallback'] = ($param['rowconditioncb'] ?? null);
        $options['dateformat'] = Config::$DisplayDateFormat; //  'm/d/Y';
        $options['datetimeformat'] = Config::$DisplayDateTimeFormat; // 'm/d/Y H:i a';
        $options['norecordtext'] = 'No Record Found';

        $grid = new \TAS\Core\Grid($options, $queryoptions);

        $defaulticons = $grid->DefaultIcon();
        foreach ($defaulticons as $index => $icon) {
            if ('edit' == $index) {
                if (false == $pages['edit'] || !$GLOBALS['permission']->CheckOperationPermission($tagname, 'edit', $GLOBALS['user']->UserRoleID)) {
                    unset($defaulticons[$index]);
                } else {
                    $defaulticons[$index]['link'] = $pages['edit'];
                }
            } elseif ('delete' == $index) {
                if (false == $pages['delete'] || !$GLOBALS['permission']->CheckOperationPermission($tagname, 'delete', $GLOBALS['user']->UserRoleID)) {
                    unset($defaulticons[$index]);
                } else {
                    $defaulticons[$index]['link'] = $pages['delete'];
                }
            }
        }

        $grid->Options['option'] = array_merge($defaulticons, ($param['extraicons'] ?? []));

        return $grid->Render();
    }

    /**
     * @deprecated 2.0.0
     *
     * To create a grid without permission, Please use GRID class instead. It will be removed from TASLib 2.0
     *
     * @param [type] $SQLQuery
     * @param [type] $pages
     * @param [type] $tagname
     * @param array $param
     */
    public static function HTMLGridForPublic($SQLQuery, $pages, $tagname, $param = [])
    {
        $options = \TAS\Core\Grid::DefaultOptions();
        $queryoptions = \TAS\Core\Grid::DefaultQueryOptions();

        $queryoptions['basicquery'] = $SQLQuery['basicquery'];
        $queryoptions['defaultorderby'] = ($param['defaultorder'] ?? '');
        $queryoptions['defaultsortdirection'] = ($param['defaultsort'] ?? '');
        $queryoptions['whereconditions'] = $SQLQuery['where'];
        $queryoptions['pagingquery'] = ($SQLQuery['pagingQuery'] ?? '');
        $queryoptions['pagingqueryend'] = ($SQLQuery['pagingQueryEnd'] ?? '');
        $queryoptions['indexfield'] = $param['indexfield'];
        $queryoptions['orderby'] = ($param['orderby'] ?? '');
        $queryoptions['noorderby'] = ($param['noorder'] ?? '');
        $queryoptions['recordshowlimit'] = ($param['showonly'] ?? 0);
        $queryoptions['tablename'] = ($param['tablename'] ?? '');

        $options['gridurl'] = $pages['gridpage'];
        $options['gridid'] = $param['tablename'];
        $options['tagname'] = $tagname;
        $options['pagesize'] = $param['pagesize'] ?? $GLOBALS['AppConfig']['PageSize'];
        $options['allowsorting'] = ($param['allowsort'] ?? true);
        $options['allowpaging'] = (isset($param['nopaging']) ? !$param['nopaging'] : true);
        $options['showtotalrecord'] = true;
        $options['totalrecordtext'] = '{totalrecord} Records';
        $options['allowselection'] = $param['allowselection'];
        $options['roworder'] = ($param['AllowRowSort'] ?? false);
        $options['fields'] = $param['fields'];

        $options['rowconditioncallback'] = ($param['rowconditioncb'] ?? null);
        $options['dateformat'] = Config::$DisplayDateFormat; //  'm/d/Y';
        $options['datetimeformat'] = Config::$DisplayDateTimeFormat; // 'm/d/Y H:i a';
        $options['norecordtext'] = 'No Record Found';

        $grid = new \TAS\Core\Grid($options, $queryoptions);
        $defaulticons = $grid->DefaultIcon();
        foreach ($defaulticons as $index => $icon) {
            if ('edit' == $index) {
                if (false == $pages['edit']) {
                    unset($defaulticons[$index]);
                }
            } elseif ('delete' == $index) {
                if (false == $pages['delete']) {
                    unset($defaulticons[$index]);
                }
            }
        }
        $grid->Options['option'] = array_merge($defaulticons, ($param['extraicons'] ?? []));

        return $grid->Render();
    }

    /**
     * Create Entry Form from Given Table Name.
     *
     * @deprecated
     *
     * @todo 1. Add Grouping Option, 2. Allow Two field to same label.
     *
     * @param mixed $table
     * @param mixed $param
     */
    public static function FormFromTable($table, $param = [])
    {
        if ('' == $table) {
            return '';
        }
        if (isset($GLOBALS['Tables'][$table])) { // We can get either the DB base Table name or our Table Array index.
            $table = $GLOBALS['Tables'][$table];
        }
        $fields = \TAS\Core\DB::GetColumns($table);
        $fieldHTML = [];

        foreach ($fields as $i => $field) {
            $fieldtype = $field['Type'];
            if (isset($param['Fields'][$field['Field']])) {
                $fieldtype = $param['Fields'][$field['Field']]['type'];
            }
            $id = ($param['Fields'][$field['Field']]['id'] ?? $field['Field']);
            $fieldname = ($param['Fields'][$field['Field']]['field'] ?? $field['Field']);
            $isrequired = ($param['Fields'][$field['Field']]['required'] ?? false);

            $fieldtype = explode('(', $fieldtype);
            $fieldtype = $fieldtype[0];

            $DoLabel = true;

            switch (strtolower($fieldtype)) {
                    case 'bigint':
                    case 'int':
                    case 'float':
                    case 'numeric':
                        $HTML = \TAS\Core\HTML::InputBox($id, $param['Fields'][$field['Field']]['value'] ?? '', $id, $isrequired, ($param['Fields'][$field['Field']]['css'] ?? 'forminput numeric'), ($param['Fields'][$field['Field']]['size'] ?? '10'), ($param['Fields'][$field['Field']]['maxlength'] ?? '10'), ($param['Fields'][$field['Field']]['additionalattr'] ?? ''));

                        break;

                    case 'datetime':
                        $HTML = \TAS\Core\HTML::InputBox($id, $param['Fields'][$field['Field']]['value'] ?? '', $id, $isrequired, ($param['Fields'][$field['Field']]['css'] ?? 'forminput datetime'), ($param['Fields'][$field['Field']]['size'] ?? '30'), ($param['Fields'][$field['Field']]['maxlength'] ?? '30'), ($param['Fields'][$field['Field']]['additionalattr'] ?? ''));

                        break;

                    case 'date':
                        $HTML = \TAS\Core\HTML::InputDate($id, $field['value'] ?? '', $id, $isrequired, Config::$WebUI_DateCSS.($field['css'] ?? 'form-control'), ($field['additionalattr'] ?? ''));

                        break;

                    case 'email':
                        $HTML = \TAS\Core\HTML::InputBox($id, $param['Fields'][$field['Field']]['value'] ?? '', $id, $isrequired, ($param['Fields'][$field['Field']]['css'] ?? 'forminput email'), ($param['Fields'][$field['Field']]['size'] ?? '30'), ($param['Fields'][$field['Field']]['maxlength'] ?? '30'), ($param['Fields'][$field['Field']]['additionalattr'] ?? ''));

                        break;

                    case 'url':
                        $HTML = \TAS\Core\HTML::InputBox($id, $param['Fields'][$field['Field']]['value'] ?? '', $id, $isrequired, ($param['Fields'][$field['Field']]['css'] ?? 'forminput url'), ($param['Fields'][$field['Field']]['size'] ?? '30'), ($param['Fields'][$field['Field']]['maxlength'] ?? '30'), ($param['Fields'][$field['Field']]['additionalattr'] ?? ''));

                        break;

                    case 'select':
                        $options = '';

                        switch ($param['Fields'][$field['Field']]['selecttype']) {
                            case 'query': // Run from DB;
                                $options = self::RecordSetToDropDown($GLOBALS['db']->Execute($param['Fields'][$field['Field']]['query']), $param['Fields'][$field['Field']]['value'] ?? '', ($param['Fields'][$field['Field']]['dbID'] ?? ''), ($param['Fields'][$field['Field']]['dbLabelField'] ?? ''), true, 'Select', '');

                                break;

                            case 'globalarray': // run from global Array
                                $options = self::ArrayToDropDown($GLOBALS[($param['Fields'][$field['Field']]['arrayname'] ?? '')], ($param['Fields'][$field['Field']]['value'] ?? ''));

                                break;

                            case 'array': // array pass along.
                                $options = self::ArrayToDropDown(($param['Fields'][$field['Field']]['arrayname'] ?? []), ($param['Fields'][$field['Field']]['value'] ?? ''));

                                break;
                        }
                        $HTML = \TAS\Core\HTML::InputSelect($id, $options, $id, $isrequired, ($param['Fields'][$field['Field']]['css'] ?? 'forminput'), ($param['Fields'][$field['Field']]['multiple'] ?? false), ($param['Fields'][$field['Field']]['multiplesize'] ?? 5), ($param['Fields'][$field['Field']]['additionalattr'] ?? ''));

                        break;

                    case 'text':
                    case 'textarea':
                    case 'longtext':
                    case 'mediumtext':
                        $HTML = \TAS\Core\HTML::InputText($id, $param['Fields'][$field['Field']]['value'] ?? '', $id, $isrequired, ($param['Fields'][$field['Field']]['css'] ?? 'forminput date'), ($param['Fields'][$field['Field']]['rows'] ?? '4'), ($param['Fields'][$field['Field']]['cols'] ?? '50'), ($param['Fields'][$field['Field']]['additionalattr'] ?? ''));

                        break;

                    case 'checkbox':
                        $HTML = \TAS\Core\HTML::InputCheckBox($id, $param['Fields'][$field['Field']]['value'] ?? '', $id, $isrequired, ($param['Fields'][$field['Field']]['css'] ?? 'forminput'), ($param['Fields'][$field['Field']]['additionalattr'] ?? ''));

                        break;

                    case 'hidden':
                        $DoLabel = false;
                        $HTML = \TAS\Core\HTML::InputHidden($id, $param['Fields'][$field['Field']]['value'] ?? '', $id);

                        break;

                    case 'inputbox':
                    case 'varchar':
                    default:
                        $HTML = \TAS\Core\HTML::InputBox($id, $param['Fields'][$field['Field']]['value'] ?? '', $id, $isrequired, ($param['Fields'][$field['Field']]['css'] ?? 'forminput'), ($param['Fields'][$field['Field']]['size'] ?? '30'), ($param['Fields'][$field['Field']]['maxlength'] ?? '30'), ($param['Fields'][$field['Field']]['additionalattr'] ?? ''));

                        break;
                }

            if ($DoLabel) {
                $HTMLLabel = \TAS\Core\HTML::Label(($param['Fields'][$field['Field']]['label'] ?? $fieldname), $id, $isrequired);
            } else {
                $HTMLLabel = '';
            }

            $fieldHTML[$fieldname] = \TAS\Core\HTML::FormField($HTMLLabel, \TAS\Core\HTML::InputWrapper($HTML));
        }

        // Iterate to $fieldHTML To put fields in order if provided
        $sortfield = [];
        if (isset($param['Fields'])) {
            foreach ($param['Fields'] as $tfield) {
                $sortfield[$tfield['displayorder']] = $tfield['field'];
            }
        } else {
            reset($fields);
            foreach ($fields as $field) {
                $sortfield[] = $field['Field'];
            }
        }

        ksort($sortfield);
        reset($sortfield);
        $HTML = '';
        if (count($sortfield) > 0) {
            foreach ($sortfield as $i => $v) {
                $HTML .= "\r\n".$fieldHTML[$v];
            }
        } else {
            foreach ($fieldHTML as $i => $v) {
                $HTML .= "\r\n".$v;
            }
        }

        return $HTML;
    }

    /**
     * Create Form HTML.
     *
     * @todo 1. Add Grouping Option,
     *
     * @param mixed $param
     */
    public static function GetFormHTML($param = [])
    {
        $fieldHTML = [];
        foreach ($param['Fields'] as $i => $field) {
            $fieldtype = $field['type'];
            $id = ($field['id'] ?? $i);
            $fieldname = ($field['field'] ?? $field['Field']);
            $isrequired = ($field['required'] ?? false);
            $fieldtype = explode('(', $fieldtype);
            $fieldtype = $fieldtype[0];

            $field['group'] = (!isset($field['group'])) ? 'nogroup' : $field['group'];

            $DoLabel = true;
            $DoLabel = (isset($field['DoLabel']) && is_bool($field['DoLabel']) ? $field['DoLabel'] : true);

            switch (strtolower($fieldtype)) {
                case 'file':
                    $HTML = \TAS\Core\HTML::InputFile($id, $id, $isrequired, 'file '.($field['css'] ?? 'form-control'), ($field['additionalattr'] ?? ''));
                    if (is_numeric($field['value']) && (int) $field['value'] > 0) {
                        $HTML .= '<span class="imagewrapper"><a class="showimage" href="#" '.(isset($field['filesource']) ? 'data-source="'.$field['filesource'].'"' : '').' data-imageid="'.$field['value'].'">View File</a> /
                        <a href="#" class="deleteimage" data-imageid="'.$field['value'].'"'.(isset($field['filesource']) ? ' data-source="'.$field['filesource'].'"' : '').'>Delete</a></span>';
                    }

                    break;

                case 'readonly':
                    $HTML = \TAS\Core\HTML::ReadOnly($field['value'] ?? '', $id, ($field['css'] ?? ''));

                    break;

                case 'bigint':
                case 'int':
                case 'float':
                case 'numeric':
                case 'number':
                    $HTML = \TAS\Core\HTML::InputBox($id, $field['value'] ?? '', $id, $isrequired, 'number '.($field['css'] ?? 'form-control'), ($field['size'] ?? '10'), ($field['maxlength'] ?? '10'), ($field['additionalattr'] ?? ''));

                    break;

                case 'datetime':
                    $HTML = \TAS\Core\HTML::InputDateTime($id, $field['value'] ?? '', $id, $isrequired, Config::$WebUI_DateTimeCSS.' '.($field['css'] ?? 'form-control'), ($field['additionalattr'] ?? ''));

                    break;

                case 'date':
                    $HTML = \TAS\Core\HTML::InputDate($id, $field['value'] ?? '', $id, $isrequired, Config::$WebUI_DateCSS.' '.($field['css'] ?? 'form-control'), ($field['additionalattr'] ?? ''));

                    break;

                case 'time':
                     $inputtime = new \TAS\Core\WebUI\InputTime();
                     $inputtime->SetAttribute('ID', $id);
                     $inputtime->SetAttribute('Name', $id);
                     $inputtime->SetAttribute('value', ($field['value'] ?? ''));
                     $HTML = $inputtime->Render();

                    break;

                case 'email':
                    $HTML = \TAS\Core\HTML::InputEmail($id, $field['value'] ?? '', $id, $isrequired, 'email '.($field['css'] ?? 'form-control'), ($field['size'] ?? '30'), ($field['maxlength'] ?? '150'), ($field['additionalattr'] ?? ''));

                    break;

                case 'phone':
                    $HTML = \TAS\Core\HTML::InputBox($id, $field['value'] ?? '', $id, $isrequired, 'phone '.($field['css'] ?? 'form-control'), ($field['size'] ?? '15'), ($field['maxlength'] ?? '15'), ($field['additionalattr'] ?? ''));

                    break;

                case 'zipcode':
                    $HTML = \TAS\Core\HTML::InputBox($id, $field['value'] ?? '', $id, $isrequired, 'zipcode '.($field['css'] ?? 'form-control'), ($field['size'] ?? '5'), ($field['maxlength'] ?? '5'), ($field['additionalattr'] ?? ''));

                    break;

                case 'url':
                    $HTML = \TAS\Core\HTML::InputBox($id, $field['value'] ?? '', $id, $isrequired, 'url '.($field['css'] ?? 'form-control'), ($field['size'] ?? '30'), ($field['maxlength'] ?? '255'), ($field['additionalattr'] ?? ''));

                    break;

                case 'select':
                    $options = '';

                    try {
                        switch ($field['selecttype']) {
                            case 'query': // Run from DB;
                                $options = self::RecordSetToDropDown($GLOBALS['db']->Execute($field['query']), $field['value'] ?? '', ($field['dbID'] ?? ''), ($field['dbLabelField'] ?? ''), ($field['showSelect'] ?? 'true'), 'Select', '');

                                break;

                            case 'recordset':
                                $options = self::RecordSetToDropDown($field['query'], $field['value'] ?? '', ($field['dbID'] ?? ''), ($field['dbLabelField'] ?? ''), ($field['showSelect'] ?? 'true'), 'Select', '');

                                break;

                            case 'multiarray':
                                $array = $field['arrayname'];
                                if (isset($field['showSelect']) && true == $field['showSelect']) {
                                    $array = [
                                        ' ' => 'Select',
                                    ] + $array;
                                }
                                $options = self::MultiArrayToDropDown($array, $field['dbID'], $field['dbLabelField'], ($field['value'] ?? ''));

                                break;

                            case 'globalarray': // run from global Array
                                $array = $GLOBALS[($field['arrayname'] ?? '')];
                                if (isset($field['showSelect']) && true == $field['showSelect']) {
                                    $array = [
                                        ' ' => 'Select',
                                    ] + $array;
                                }
                                $options = self::ArrayToDropDown($array, ($field['value'] ?? ''));

                                break;

                            case 'array': // array pass along.
                                $array = ($field['arrayname'] ?? []);
                                if (isset($field['showSelect']) && true == $field['showSelect']) {
                                    $array = [
                                        ' ' => 'Select',
                                    ] + $array;
                                }
                                $options = self::ArrayToDropDown($array, ($field['value'] ?? ''));

                                break;
                        }
                    } catch (\Exception $ex) {
                        trigger_error('Unable to create Select list, argument invalid', E_USER_ERROR);
                    }
                    $multi = ($field['multiple'] ?? false);
                    $HTML = \TAS\Core\HTML::InputSelect($id, $options, $id.($multi ? '[]' : ''), $isrequired, ($field['css'] ?? 'form-control'), $multi, ($field['multiplesize'] ?? 5), ($field['additionalattr'] ?? ''));

                    break;

                case 'text':
                case 'textarea':
                case 'longtext':
                case 'mediumtext':
                    $HTML = \TAS\Core\HTML::InputText($id, $field['value'] ?? '', $id, $isrequired, ($field['css'] ?? 'form-control'), ($field['rows'] ?? '4'), ($field['cols'] ?? '50'), ($field['additionalattr'] ?? ''));

                    break;

                case 'checkbox':
                    $HTML = \TAS\Core\HTML::InputCheckBox($id, $field['value'] ?? '', $id, $isrequired, ($field['css'] ?? 'form-control'), ($field['additionalattr'] ?? ''));

                    break;

                case 'hidden':
                    $DoLabel = false;
                    $HTML = \TAS\Core\HTML::InputHidden($id, $field['value'] ?? '', $id);

                    break;

                case 'checklist':
                    switch ($field['selecttype']) {
                        case 'array':
                            $HTML = \TAS\Core\UI::ArrayToCheckRadioList($field['arrayname'], $field['value'], $id, 'checkbox');

                            break;

                        default:
                            $HTML = \TAS\Core\UI::RecordSetToCheckRadioList($field['recordset'], $field['value'], $field['dbID'], $field['dbLabelField'], $id, $isrequired, 'checkbox');

                            break;
                    }

                    break;

                case 'radiolist':
                    switch ($field['selecttype']) {
                        case 'array':
                            $HTML = \TAS\Core\UI::ArrayToCheckRadioList($field['arrayname'], $field['value'], $id, 'radio');

                            break;

                        default:
                            $HTML = \TAS\Core\UI::RecordSetToCheckRadioList($field['recordset'], $field['value'], $field['dbID'], $field['dbLabelField'], $id, $isrequired, 'radio');

                            break;
                    }

                    break;

                case 'password':
                    $HTML = \TAS\Core\HTML::InputPassword($id, $field['value'] ?? '', $id, $isrequired, ($field['css'] ?? 'form-control'), ($field['size'] ?? '30'), ($field['maxlength'] ?? '30'), ($field['additionalattr'] ?? ''));

                    break;

                case 'cb':
                    $HTML = call_user_func($field['function'], $field); // @remark, $field data array is only parameter.

                    break;

                case 'color':
                    $HTML = \TAS\Core\HTML::InputColour($id, $field['value'] ?? '', $id, $isrequired, ($field['css'] ?? 'form-control'), ($field['additionalattr'] ?? ''));

                    break;

                case 'inputbox':
                case 'varchar':
                case 'string':
                default:
                    $HTML = \TAS\Core\HTML::InputBox($id, $field['value'] ?? '', $id, $isrequired, ($field['css'] ?? 'form-control'), ($field['size'] ?? '30'), ($field['maxlength'] ?? '30'), ($field['additionalattr'] ?? ''));

                    break;
            }
            $HTMLLabel = '';
            if ($DoLabel) {
                $HTMLLabel = \TAS\Core\HTML::Label(($field['label'] ?? $fieldname), $id, $isrequired);
            }

            if (array_key_exists('prefix', $field)) {
                $HTML = '<span class="prefixnote">'.$field['prefix'].'</span>'.$HTML;
            }

            if (isset($field['shortnote'])) {
                $HTML .= '<span class="fieldnote field_'.$field['id'].'">'.$field['shortnote'].'</span>';
            }

            $groupName = 'nogroup';
            if (isset($param['Group'], $param['Group'][$field['group']])) {
                $groupName = $field['group'];
            }

            if (isset($field['DoWrapper']) && false == $field['DoWrapper']) {
                $fieldHTML[$groupName][$fieldname]['html'] = $HTMLLabel.$HTML;
            } else {
                $fieldHTML[$groupName][$fieldname]['html'] = \TAS\Core\HTML::FormField($HTMLLabel, \TAS\Core\HTML::InputWrapper($HTML), ($field['wrappertag'] ?? ''));
            }

            $fieldHTML[$groupName][$fieldname]['displayorder'] = ($field['displayorder'] ?? 1);
        }

        // Iterate to $fieldHTML To put fields in order if provided
        $HTML = '';
        if (isset($param['Group'])) {
            foreach ($param['Group'] as $group => $info) {
                if (isset($fieldHTML[$group])) {
                    $tmp[$group] = $fieldHTML[$group];
                    unset($fieldHTML[$group]);
                }
            }
            $tmp2 = $fieldHTML;
            $fieldHTML = $tmp;
            $fieldHTML = array_merge($fieldHTML, $tmp2);
            unset($tmp, $tmp2);

            foreach ($fieldHTML as $group => $fieldinfo) {
                $sortfield = [];
                foreach ($fieldinfo as $fieldname => $tfield) {
                    $sortfield[$tfield['displayorder']] = $fieldname;
                }
                ksort($sortfield);
                reset($sortfield);
                if (isset($param['Group'][$group])) {
                    $HTML .= '<fieldset class="'.$group.'"><legend>'.$param['Group'][$group]['legend'].'</legend>';
                }

                foreach ($sortfield as $i => $v) {
                    $HTML .= "\r\n".$fieldHTML[$group][$v]['html'];
                }
                if (isset($param['Group'][$group])) {
                    $HTML .= '</fieldset>';
                }
            }
        } else {
            foreach ($fieldHTML as $group => $fieldinfo) {
                $sortfield = [];
                foreach ($fieldinfo as $fieldname => $tfield) {
                    $sortfield[$tfield['displayorder']] = $fieldname;
                }
                ksort($sortfield);
                reset($sortfield);
                if (isset($param['Group'][$group])) {
                    $HTML .= '<fieldset class="'.$group.'"><legend>'.$param['Group'][$group]['legend'].'</legend>';
                }
                foreach ($sortfield as $i => $v) {
                    $HTML .= "\r\n".$fieldHTML[$group][$v]['html'];
                }
                if (isset($param['Group'][$group])) {
                    $HTML .= '</fieldset>';
                }
            }
        }

        return $HTML;
    }

    /**
     * Create HTML for Form in Readonly Mode.
     *
     * @param mixed $param
     */
    public static function GetReadOnlyHTML($param = [])
    {
        $fieldHTML = [];
        foreach ($param['Fields'] as $i => $field) {
            $fieldtype = $field['type'];
            $id = ($field['id'] ?? $i);
            $fieldname = ($field['field'] ?? $field['Field']);
            $isrequired = ($field['required'] ?? false);
            $fieldtype = explode('(', $fieldtype);
            $fieldtype = $fieldtype[0];

            $field['group'] = (!isset($field['group'])) ? 'nogroup' : $field['group'];

            $DoLabel = true;
            $DoLabel = (isset($field['DoLabel']) && is_bool($field['DoLabel']) ? $field['DoLabel'] : true);

            switch (strtolower($fieldtype)) {
                case 'file':
                    $DoLabel = false;
                    $HTML = '';

                    break;

                case 'radiolist':
                case 'checklist':
                    if ('query' == $field['selecttype']) {
                        $field['selecttype'] = 'recordset';
                        $field['query'] = $field['recordset'];
                    }
                    // no break
                case 'select':
                    $options = [];

                    try {
                        switch ($field['selecttype']) {
                            case 'query': // Run from DB;
                                $rs = $GLOBALS['db']->Execute($field['query']);
                                if (null != $rs && \TAS\Core\DB::Count($rs) > 0) {
                                    foreach ($rs as $row) {
                                        if (false === \strpos($field['dbLabelField'], '{')) {
                                            $options[$row[$field['dbID']]] = $row[$field['dbLabelField']];
                                        } else {
                                            $options[$row[$field['dbID']]] = \TAS\Core\TemplateHandler::PrepareContent($field['dbLabelField'], $row);
                                        }
                                    }
                                }

                                break;

                            case 'recordset':
                                if (null != $field['query'] && \TAS\Core\DB::Count($field['query']) > 0) {
                                    foreach ($field['query'] as $row) {
                                        if (false === \strpos($field['dbLabelField'], '{')) {
                                            $options[$row[$field['dbID']]] = $row[$field['dbLabelField']];
                                        } else {
                                            $options[$row[$field['dbID']]] = \TAS\Core\TemplateHandler::PrepareContent($field['dbLabelField'], $row);
                                        }
                                    }
                                }

                                break;

                                break;

                            case 'globalarray': // run from global Array
                                $options = $GLOBALS[($field['arrayname'] ?? '')];

                                break;

                            case 'array': // array pass along.
                                $options = ($field['arrayname'] ?? []);

                                break;
                        }
                    } catch (\Exception $ex) {
                        trigger_error('Unable to create Select list, argument invalid', E_USER_ERROR);
                    }
                    $multi = ($field['multiple'] ?? false);
                    $HTML = '';
                    foreach ($options as $key => $option) {
                        if ((is_array($field['value']) && in_array($key, $field['value']))
                            || ($key == $field['value'])) {
                            $HTML .= \TAS\Core\HTML::ReadOnly($option);
                        }
                    }

                    break;

                case 'checkbox':
                    $HTML = (isset($field['value']) && (1 == $field['value'] || true == $field['value'])) ? 'Yes' : 'No';

                    break;

                case 'hidden':
                    $DoLabel = false;
                    $HTML = '';

                    break;

                case 'cb':
                    $HTML = call_user_func($field['function'], $field, true); // @remark, $field data array is only parameter.

                    break;

                case 'color':
                case 'password':
                case 'text':
                case 'textarea':
                case 'longtext':
                case 'mediumtext':
                case 'inputbox':
                case 'varchar':
                case 'string':
                case 'bigint':
                case 'int':
                case 'float':
                case 'numeric':
                case 'number':
                case 'datetime':
                case 'date':
                case 'email':
                case 'phone':
                case 'zipcode':
                case 'url':
                default:
                        $HTML = \TAS\Core\HTML::ReadOnly($field['value'] ?? '', $id, ($field['css'] ?? ''));

                    break;
            }
            if ($DoLabel) {
                $HTMLLabel = \TAS\Core\HTML::Label(($field['label'] ?? $fieldname), $id, $isrequired);
            } else {
                $HTMLLabel = '';
            }

            $groupName = 'nogroup';
            if (isset($param['Group'], $param['Group'][$field['group']])) {
                $groupName = $field['group'];
            }

            if (isset($field['DoWrapper']) && false == $field['DoWrapper']) {
                $fieldHTML[$groupName][$fieldname]['html'] = $HTMLLabel.$HTML;
            } else {
                $fieldHTML[$groupName][$fieldname]['html'] = \TAS\Core\HTML::FormField($HTMLLabel, \TAS\Core\HTML::InputWrapper($HTML), ($field['wrappertag'] ?? ''));
            }

            $fieldHTML[$groupName][$fieldname]['displayorder'] = ($field['displayorder'] ?? 1);
        }

        // Iterate to $fieldHTML To put fields in order if provided
        $HTML = '';
        if (isset($param['Group'])) {
            foreach ($param['Group'] as $group => $info) {
                if (isset($fieldHTML[$group])) {
                    $tmp[$group] = $fieldHTML[$group];
                    unset($fieldHTML[$group]);
                }
            }
            $tmp2 = $fieldHTML;
            $fieldHTML = $tmp;
            $fieldHTML = array_merge($fieldHTML, $tmp2);
            unset($tmp, $tmp2);

            foreach ($fieldHTML as $group => $fieldinfo) {
                $sortfield = [];
                foreach ($fieldinfo as $fieldname => $tfield) {
                    $sortfield[$tfield['displayorder']] = $fieldname;
                }
                ksort($sortfield);
                reset($sortfield);
                if (isset($param['Group'][$group])) {
                    $HTML .= '<fieldset class="'.$group.'"><legend>'.$param['Group'][$group]['legend'].'</legend>';
                }

                foreach ($sortfield as $i => $v) {
                    $HTML .= "\r\n".$fieldHTML[$group][$v]['html'];
                }
                if (isset($param['Group'][$group])) {
                    $HTML .= '</fieldset>';
                }
            }
        } else {
            foreach ($fieldHTML as $group => $fieldinfo) {
                $sortfield = [];
                foreach ($fieldinfo as $fieldname => $tfield) {
                    $sortfield[$tfield['displayorder']] = $fieldname;
                }
                ksort($sortfield);
                reset($sortfield);
                if (isset($param['Group'][$group])) {
                    $HTML .= '<fieldset class="'.$group.'"><legend>'.$param['Group'][$group]['legend'].'</legend>';
                }
                foreach ($sortfield as $i => $v) {
                    $HTML .= "\r\n".$fieldHTML[$group][$v]['html'];
                }
                if (isset($param['Group'][$group])) {
                    $HTML .= '</fieldset>';
                }
            }
        }

        return $HTML;
    }

    /**
     * Display the UI message on screen for form.
     *
     * @param unknown_type $messages
     */
    public static function UIMessageDisplay($messages)
    {
        if (!is_array($messages)) {
            return '';
        }
        if (is_array($messages) && count($messages) <= 0) {
            return '';
        }
        $returnString = '<div class="hidemessage">';
        $returnString .= '<ul>';
        reset($messages);
        foreach ($messages as $i => $value) {
            if ($value['level'] < 10) {
                $returnString .= '<li class="alert alert-success mrgntop">'.$value['message'].'</li>';
            } else {
                $returnString .= '<li class="alert alert-danger mrgntop">'.$value['message'].'</li>';
            }
        }
        $returnString .= '</ul>';
        $returnString .= '</div>';

        return $returnString;
    }
}
