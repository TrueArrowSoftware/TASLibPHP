<?php

namespace TAS\Core;

class UI
{
    public static function ImageURLFinder($imageObj, $width, $height)
    {
        if (is_array($imageObj['thumbnails']) && count($imageObj['thumbnails']) > 0 && isset($imageObj['thumbnails']['w'.$width.'.h'.$height])) {
            return $imageObj['baseurl'].$imageObj['thumbnails']['w'.$width.'.h'.$height];
        } else {
            return ImageFile::GetResizedImageURL($imageObj['physicalpath'], $imageObj['url'], $width, $height, $GLOBALS['AppConfig']['NoImage_Listing']);
        }
    }

    public static function GetEmailContent($EmailID)
    {
        $rsEmail = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['emailcms'].' where id='.(int) $EmailID);
        if ($GLOBALS['db']->RowCount($rsEmail) > 0) {
            $row = $GLOBALS['db']->Fetch($rsEmail);

            return $row;
        } else {
            return false;
        }
    }

    public static function ArrayToDropDown($sourceArray, $selectedvalue)
    {
        if (is_array($sourceArray)) {
            $val = '';
            foreach ($sourceArray as $i => $v) {
                if (is_array($selectedvalue)) {
                    $select = in_array(\TAS\Core\DataFormat::DoSecure($i), $selectedvalue) ? true : false;
                } else {
                    $select = (\TAS\Core\DataFormat::DoSecure($i) == $selectedvalue) ? true : false;
                }
                $val .= '<option value="'.\TAS\Core\DataFormat::DoSecure($i).'"'.($select ? ' selected="selected"' : '').'>'.$v."</option>\r\n";
            }

            return $val;
        } else {
            return '';
        }
    }

    /**
     * function will create the HTML option list from record set.
     */
    public static function RecordSetToDropDown($rs, $selectedvalue, $indexCol, $labelCol, $showSelect = true, $SelectText = 'Select')
    {
        global $db;
        $list = '';
        $SelectionDone = false;
        if ($db->RowCount($rs) > 0) {
            $columns = explode(' ', $labelCol);
            while ($row = $db->FetchArray($rs)) {
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
                if ($row[$indexCol] == $selectedvalue) {
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

    public static function ArrayToCheckRadioList($array, $selectedvalues, $name = 'list', $type = 'checkbox', $isrequired = false, $labelformat = '{text}')
    {
        $list = '';
        $SelectionDone = false;
        if (!is_array($selectedvalues)) {
            if (is_numeric($selectedvalues)) {
                $selectedvalues = array(
                    $selectedvalues,
                );
            } else {
                $selectedvalues = explode(',', $selectedvalues);
            }
        }
        if (is_array($array) && count($array) > 0) {
            $list .= '<ul class="rslist">';
            foreach ($array as $i => $v) {
                $text = \TAS\Core\TemplateHandler::PrepareContent($labelformat, array(
                    'index' => $i,
                    'text' => $v,
                ));
                $list .= '<li><input type="'.$type.'" '.(($isrequired) ? ' class="required" ' : '').(($type == 'radio') ? ' value="'.$i.'"' : ' value="on"').(($type == 'radio') ? ' name="'.$name.'"' : ' name="'.$name.'['.$i.']"').' '.(in_array($i, $selectedvalues) ? 'checked="checked"' : '').'>'.$text."</li>\n";
            }
            $list .= '</ul>';
        }

        return $list;
    }

    public static function RecordSetToCheckRadioList($rs, $selectedvalues, $indexCol, $labelformat, $name = 'list', $isrequired = false, $type = 'checkbox')
    {
        $list = '';
        $SelectionDone = false;
        if (!is_array($selectedvalues)) {
            if (is_numeric($selectedvalues)) {
                $selectedvalues = array(
                    $selectedvalues,
                );
            } else {
                $selectedvalues = explode(',', $selectedvalues);
            }
        }
        if ($GLOBALS['db']->RowCount($rs) > 0) {
            $list .= '<ul class="rslist">';
            while ($row = $GLOBALS['db']->FetchArray($rs)) {
                $text = \TAS\Core\TemplateHandler::PrepareContent($labelformat, $row);
                $list .= '<li><input type="'.$type.'" '.(($isrequired) ? ' class="required" ' : '').(($type == 'radio') ? ' value="'.$row[$indexCol].'"' : ' value="on"').(($type == 'radio') ? ' name="'.$name.'"' : ' name="'.$name.'['.$row[$indexCol].']"').' '.(in_array($row[$indexCol], $selectedvalues) ? 'checked="checked"' : '').'>'.$text."</li>\n";
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
     * @param array  $param
     *
     * @return void
     */
    public static function HTMLGridFromRecordSet($SQLQuery, $pages, $tagname, $param = array())
    {
        $options = \TAS\Core\Grid::DefaultOptions();
        $queryoptions = \TAS\Core\Grid::DefaultQueryOptions();

        $queryoptions['basicquery'] = $SQLQuery['basicquery'];
        $queryoptions['defaultorderby'] = (isset($param['defaultorder']) ? $param['defaultorder'] : '');
        $queryoptions['defaultsortdirection'] = (isset($param['defaultsort']) ? $param['defaultsort'] : '');
        $queryoptions['whereconditions'] = $SQLQuery['where'];
        $queryoptions['pagingquery'] = (isset($SQLQuery['pagingQuery']) ? $SQLQuery['pagingQuery'] : '');
        $queryoptions['pagingqueryend'] = (isset($SQLQuery['pagingQueryEnd']) ? $SQLQuery['pagingQueryEnd'] : '');
        $queryoptions['indexfield'] = $param['indexfield'];
        $queryoptions['orderby'] = (isset($SQLQuery['orderby']) ? $SQLQuery['orderby'] : '');
        $queryoptions['noorderby'] = (isset($SQLQuery['noorder']) ? $SQLQuery['noorder'] : '');
        $queryoptions['recordshowlimit'] = (isset($SQLQuery['showonly']) ? $SQLQuery['showonly'] : 0);
        $queryoptions['tablename'] = (isset($param['tablename']) ? $param['tablename'] : '');

        $options['gridurl'] = $pages['gridpage'];
        $options['gridid'] = $param['tablename'];
        $options['tagname'] = $tagname;
        $options['pagesize'] = isset($param['pagesize']) ? $param['pagesize'] : $GLOBALS['AppConfig']['PageSize'];
        $options['allowsorting'] = (isset($param['allowsort']) ? $param['allowsort'] : true);
        $options['allowpaging'] = (isset($param['nopaging']) ? !$param['nopaging'] : true);
        $options['showtotalrecord'] = true;
        $options['totalrecordtext'] = '{totalrecord} Records';
        $options['allowselection'] = $param['allowselection'];
        $options['roworder'] = (isset($param['AllowRowSort']) ? $param['AllowRowSort'] : false);
        $options['fields'] = $param['fields'];

        $options['rowconditioncallback'] = (isset($param['rowconditioncb']) ? $param['rowconditioncb'] : null);
        $options['dateformat'] = 'm/d/Y';
        $options['datetimeformat'] = 'm/d/Y H:i a';
        $options['norecordtext'] = 'No Record Found';

        $grid = new \TAS\Core\Grid($options, $queryoptions);

        $defaulticons = $grid->DefaultIcon();
        foreach ($defaulticons as $index => $icon) {
            if ($index == 'edit') {
                if ($pages['edit'] == false || !$GLOBALS['permission']->CheckOperationPermission($tagname, 'edit', $GLOBALS['user']->UserRoleID)) {
                    unset($defaulticons[$index]);
                } else {
                    $defaulticons[$index]['link'] = $pages['edit'];
                }
            } elseif ($index == 'delete') {
                if ($pages['delete'] == false || !$GLOBALS['permission']->CheckOperationPermission($tagname, 'delete', $GLOBALS['user']->UserRoleID)) {
                    unset($defaulticons[$index]);
                } else {
                    $defaulticons[$index]['link'] = $pages['delete'];
                }
            }
        }

        $grid->Options['option'] = array_merge($defaulticons, (isset($param['extraicons']) ? $param['extraicons'] : array()));

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
     * @param array  $param
     *
     * @return void
     */
    public static function HTMLGridForPublic($SQLQuery, $pages, $tagname, $param = array())
    {
        $options = \TAS\Core\Grid::DefaultOptions();
        $queryoptions = \TAS\Core\Grid::DefaultQueryOptions();

        $queryoptions['basicquery'] = $SQLQuery['basicquery'];
        $queryoptions['defaultorderby'] = (isset($param['defaultorder']) ? $param['defaultorder'] : '');
        $queryoptions['defaultsortdirection'] = (isset($param['defaultsort']) ? $param['defaultsort'] : '');
        $queryoptions['whereconditions'] = $SQLQuery['where'];
        $queryoptions['pagingquery'] = (isset($SQLQuery['pagingQuery']) ? $SQLQuery['pagingQuery'] : '');
        $queryoptions['pagingqueryend'] = (isset($SQLQuery['pagingQueryEnd']) ? $SQLQuery['pagingQueryEnd'] : '');
        $queryoptions['indexfield'] = $param['indexfield'];
        $queryoptions['orderby'] = (isset($SQLQuery['orderby']) ? $SQLQuery['orderby'] : '');
        $queryoptions['noorderby'] = (isset($SQLQuery['noorder']) ? $SQLQuery['noorder'] : '');
        $queryoptions['recordshowlimit'] = (isset($SQLQuery['showonly']) ? $SQLQuery['showonly'] : 0);
        $queryoptions['tablename'] = (isset($SQLQuery['tablename']) ? $SQLQuery['tablename'] : '');

        $options['gridurl'] = $pages['gridpage'];
        $options['gridid'] = $param['tablename'];
        $options['tagname'] = $tagname;
        $options['pagesize'] = isset($param['pagesize']) ? $param['pagesize'] : $GLOBALS['AppConfig']['PageSize'];
        $options['allowsorting'] = (isset($param['allowsort']) ? $param['allowsort'] : true);
        $options['allowpaging'] = (isset($param['nopaging']) ? !$param['nopaging'] : true);
        $options['showtotalrecord'] = true;
        $options['totalrecordtext'] = '{totalrecord} Records';
        $options['allowselection'] = $param['allowselection'];
        $options['roworder'] = (isset($param['AllowRowSort']) ? $param['AllowRowSort'] : false);
        $options['fields'] = $param['fields'];

        $options['rowconditioncallback'] = (isset($param['rowconditioncb']) ? $param['rowconditioncb'] : null);
        $options['dateformat'] = 'm/d/Y';
        $options['datetimeformat'] = 'm/d/Y H:i a';
        $options['norecordtext'] = 'No Record Found';

        $grid = new \TAS\Core\Grid($options, $queryoptions);
        $defaulticons = $grid->DefaultIcon();
        foreach ($defaulticons as $index => $icon) {
            if ($index == 'edit') {
                if ($pages['edit'] == false) {
                    unset($defaulticons[$index]);
                }
            } elseif ($index == 'delete') {
                if ($pages['delete'] == false) {
                    unset($defaulticons[$index]);
                }
            }
        }
        $grid->Options['option'] = array_merge($defaulticons, (isset($param['extraicons']) ? $param['extraicons'] : array()));

        return $grid->Render();
    }

    /**
     * Create Entry Form from Given Table Name.
     *
     * @deprecated
     *
     * @todo 1. Add Grouping Option, 2. Allow Two field to same label.
     */
    public static function FormFromTable($table, $param = array())
    {
        if ($table == '') {
            return '';
        }
        if (isset($GLOBALS['Tables'][$table])) { // We can get either the DB base Table name or our Table Array index.
            $table = $GLOBALS['Tables'][$table];
        }
        $fields = \TAS\Core\DB::GetColumns($table);
        $fieldHTML = array();

        foreach ($fields as $i => $field) {
            $fieldtype = $field['Type'];
            if (isset($param['Fields'][$field['Field']])) {
                $fieldtype = $param['Fields'][$field['Field']]['type'];
            }
            $id = (isset($param['Fields'][$field['Field']]['id']) ? $param['Fields'][$field['Field']]['id'] : $field['Field']);
            $fieldname = (isset($param['Fields'][$field['Field']]['field']) ? $param['Fields'][$field['Field']]['field'] : $field['Field']);
            $isrequired = (isset($param['Fields'][$field['Field']]['required']) ? $param['Fields'][$field['Field']]['required'] : false);

            $fieldtype = explode('(', $fieldtype);
            $fieldtype = $fieldtype[0];

            $DoLabel = true;
            switch (strtolower($fieldtype)) {
                    case 'bigint':
                    case 'int':
                    case 'float':
                    case 'numeric':
                        $HTML = \TAS\Core\HTML::InputBox($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput numeric'), (isset($param['Fields'][$field['Field']]['size']) ? $param['Fields'][$field['Field']]['size'] : '10'), (isset($param['Fields'][$field['Field']]['maxlength']) ? $param['Fields'][$field['Field']]['maxlength'] : '10'), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;

                    case 'datetime':
                        $HTML = \TAS\Core\HTML::InputBox($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput datetime'), (isset($param['Fields'][$field['Field']]['size']) ? $param['Fields'][$field['Field']]['size'] : '30'), (isset($param['Fields'][$field['Field']]['maxlength']) ? $param['Fields'][$field['Field']]['maxlength'] : '30'), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;
                    case 'date':
                        $HTML = \TAS\Core\HTML::InputBox($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput date'), (isset($param['Fields'][$field['Field']]['size']) ? $param['Fields'][$field['Field']]['size'] : '30'), (isset($param['Fields'][$field['Field']]['maxlength']) ? $param['Fields'][$field['Field']]['maxlength'] : '30'), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;
                    case 'email':
                        $HTML = \TAS\Core\HTML::InputBox($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput email'), (isset($param['Fields'][$field['Field']]['size']) ? $param['Fields'][$field['Field']]['size'] : '30'), (isset($param['Fields'][$field['Field']]['maxlength']) ? $param['Fields'][$field['Field']]['maxlength'] : '30'), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;
                    case 'url':
                        $HTML = \TAS\Core\HTML::InputBox($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput url'), (isset($param['Fields'][$field['Field']]['size']) ? $param['Fields'][$field['Field']]['size'] : '30'), (isset($param['Fields'][$field['Field']]['maxlength']) ? $param['Fields'][$field['Field']]['maxlength'] : '30'), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;

                    case 'select':
                        $options = '';
                        switch ($param['Fields'][$field['Field']]['selecttype']) {
                            case 'query': // Run from DB;
                                $options = self::RecordSetToDropDown($GLOBALS['db']->Execute($param['Fields'][$field['Field']]['query']), isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', (isset($param['Fields'][$field['Field']]['dbID']) ? $param['Fields'][$field['Field']]['dbID'] : ''), (isset($param['Fields'][$field['Field']]['dbLabelField']) ? $param['Fields'][$field['Field']]['dbLabelField'] : ''), true, 'Select', '');
                                break;
                            case 'globalarray': // run from global Array
                                $options = self::ArrayToDropDown($GLOBALS[(isset($param['Fields'][$field['Field']]['arrayname']) ? $param['Fields'][$field['Field']]['arrayname'] : '')], (isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : ''));
                                break;
                            case 'array': // array pass along.
                                $options = self::ArrayToDropDown((isset($param['Fields'][$field['Field']]['arrayname']) ? $param['Fields'][$field['Field']]['arrayname'] : array()), (isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : ''));
                                break;
                        }
                        $HTML = \TAS\Core\HTML::InputSelect($id, $options, $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput'), (isset($param['Fields'][$field['Field']]['multiple']) ? $param['Fields'][$field['Field']]['multiple'] : false), (isset($param['Fields'][$field['Field']]['multiplesize']) ? $param['Fields'][$field['Field']]['multiplesize'] : 5), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;
                    case 'text':
                    case 'textarea':
                    case 'longtext':
                    case 'mediumtext':
                        $HTML = \TAS\Core\HTML::InputText($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput date'), (isset($param['Fields'][$field['Field']]['rows']) ? $param['Fields'][$field['Field']]['rows'] : '4'), (isset($param['Fields'][$field['Field']]['cols']) ? $param['Fields'][$field['Field']]['cols'] : '50'), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;
                    case 'checkbox':
                        $HTML = \TAS\Core\HTML::InputCheckBox($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput'), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;
                    case 'hidden':
                        $DoLabel = false;
                        $HTML = \TAS\Core\HTML::InputHidden($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id);
                        break;
                    case 'inputbox':
                    case 'varchar':
                    default:
                        $HTML = \TAS\Core\HTML::InputBox($id, isset($param['Fields'][$field['Field']]['value']) ? $param['Fields'][$field['Field']]['value'] : '', $id, $isrequired, (isset($param['Fields'][$field['Field']]['css']) ? $param['Fields'][$field['Field']]['css'] : 'forminput'), (isset($param['Fields'][$field['Field']]['size']) ? $param['Fields'][$field['Field']]['size'] : '30'), (isset($param['Fields'][$field['Field']]['maxlength']) ? $param['Fields'][$field['Field']]['maxlength'] : '30'), (isset($param['Fields'][$field['Field']]['additionalattr']) ? $param['Fields'][$field['Field']]['additionalattr'] : ''));
                        break;
                }

            if ($DoLabel) {
                $HTMLLabel = \TAS\Core\HTML::Label((isset($param['Fields'][$field['Field']]['label']) ? $param['Fields'][$field['Field']]['label'] : $field['Field']), $id, $isrequired);
            } else {
                $HTMLLabel = '';
            }

            $fieldHTML[$fieldname] = \TAS\Core\HTML::FormField($HTMLLabel, \TAS\Core\HTML::InputWrapper($HTML));
        }

        // Iterate to $fieldHTML To put fields in order if provided
        $sortfield = array();
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
     */
    public static function GetFormHTML($param = array())
    {
        $fieldHTML = array();
        foreach ($param['Fields'] as $i => $field) {
            $fieldtype = $field['type'];
            $id = (isset($field['id']) ? $field['id'] : $i);
            $fieldname = (isset($field['field']) ? $field['field'] : $field['Field']);
            $isrequired = (isset($field['required']) ? $field['required'] : false);
            $fieldtype = explode('(', $fieldtype);
            $fieldtype = $fieldtype[0];

            $field['group'] = (!isset($field['group'])) ? 'nogroup' : $field['group'];

            $DoLabel = true;
            $DoLabel = (isset($field['DoLabel']) && is_bool($field['DoLabel']) ? $field['DoLabel'] : true);

            switch (strtolower($fieldtype)) {
                case 'file':
                    $HTML = \TAS\Core\HTML::InputFile($id, $id, $isrequired, 'file '.(isset($field['css']) ? form - control : 'form-control'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    if ($field['value'] > 0) {
                        $HTML .= '<span class="imagewrapper"><a class="showimage" href="#" data-imageid="'.$field['value'].'">Image</a> /
									<a href="#" class="deleteimage" data-imageid="'.$field['value'].'">Delete</a></span>';
                    }
                    break;
                case 'readonly':
                    $HTML = \TAS\Core\HTML::ReadOnly(isset($field['value']) ? $field['value'] : '', $id, (isset($field['css']) ? $field['css'] : ''));
                    break;
                case 'bigint':
                case 'int':
                case 'float':
                case 'numeric':
                case 'number':
                    $HTML = \TAS\Core\HTML::InputBox($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, 'number '.(isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '10'), (isset($field['maxlength']) ? $field['maxlength'] : '10'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'datetime':
                    $HTML = \TAS\Core\HTML::InputBox($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, 'datetime '.(isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '30'), (isset($field['maxlength']) ? $field['maxlength'] : '30'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'date':
                    $HTML = \TAS\Core\HTML::InputDate($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, 'date '.(isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '30'), (isset($field['maxlength']) ? $field['maxlength'] : '30'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'email':
                    $HTML = \TAS\Core\HTML::InputEmail($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, 'email '.(isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '30'), (isset($field['maxlength']) ? $field['maxlength'] : '150'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'phone':
                    $HTML = \TAS\Core\HTML::InputBox($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, 'phone '.(isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '15'), (isset($field['maxlength']) ? $field['maxlength'] : '15'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'zipcode':
                    $HTML = \TAS\Core\HTML::InputBox($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, 'zipcode '.(isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '5'), (isset($field['maxlength']) ? $field['maxlength'] : '5'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'url':
                    $HTML = \TAS\Core\HTML::InputBox($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, 'url '.(isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '30'), (isset($field['maxlength']) ? $field['maxlength'] : '255'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;

                case 'select':
                    $options = '';
                    try {
                        switch ($field['selecttype']) {
                            case 'query': // Run from DB;
                                $options = self::RecordSetToDropDown($GLOBALS['db']->Execute($field['query']), isset($field['value']) ? $field['value'] : '', (isset($field['dbID']) ? $field['dbID'] : ''), (isset($field['dbLabelField']) ? $field['dbLabelField'] : ''), (isset($field['showSelect']) ? $field['showSelect'] : 'true'), 'Select', '');
                                break;
                            case 'recordset':
                                $options = self::RecordSetToDropDown($field['query'], isset($field['value']) ? $field['value'] : '', (isset($field['dbID']) ? $field['dbID'] : ''), (isset($field['dbLabelField']) ? $field['dbLabelField'] : ''), (isset($field['showSelect']) ? $field['showSelect'] : 'true'), 'Select', '');
                                break;
                            case 'globalarray': // run from global Array
                                $array = $GLOBALS[(isset($field['arrayname']) ? $field['arrayname'] : '')];
                                if (isset($field['showSelect']) && $field['showSelect'] == true) {
                                    $array = array(
                                        ' ' => 'Select',
                                    ) + $array;
                                }
                                $options = self::ArrayToDropDown($array, (isset($field['value']) ? $field['value'] : ''));
                                break;
                            case 'array': // array pass along.
                                $array = (isset($field['arrayname']) ? $field['arrayname'] : array());
                                if (isset($field['showSelect']) && $field['showSelect'] == true) {
                                    $array = array(
                                        ' ' => 'Select',
                                    ) + $array;
                                }
                                $options = self::ArrayToDropDown($array, (isset($field['value']) ? $field['value'] : ''));
                                break;
                        }
                    } catch (\Exception $ex) {
                        trigger_error('Unable to create Select list, argument invalid', E_USER_ERROR);
                    }
                    $multi = (isset($field['multiple']) ? $field['multiple'] : false);
                    $HTML = \TAS\Core\HTML::InputSelect($id, $options, $id.($multi ? '[]' : ''), $isrequired, (isset($field['css']) ? $field['css'] : 'form-control'), $multi, (isset($field['multiplesize']) ? $field['multiplesize'] : 5), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'text':
                case 'textarea':
                case 'longtext':
                case 'mediumtext':
                    $HTML = \TAS\Core\HTML::InputText($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, (isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['rows']) ? $field['rows'] : '4'), (isset($field['cols']) ? $field['cols'] : '50'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'checkbox':
                    $HTML = \TAS\Core\HTML::InputCheckBox($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, (isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'hidden':
                    $DoLabel = false;
                    $HTML = \TAS\Core\HTML::InputHidden($id, isset($field['value']) ? $field['value'] : '', $id);
                    break;
                case 'checklist':
                    switch ($field['selecttype']) {
                        case 'array':
                            $HTML = \TAS\Core\UI::ArrayToCheckRadioList($field['arrayname'], $field['value'], $id);
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
                            $HTML = \TAS\Core\UI::RecordSetToCheckRadioList($field['recordset'], $field['value'], $field['dbID'], $field['dbLabelField'], $id, $isrequired, 'radiobutton');
                            break;
                    }
                    break;
                case 'password':
                    $HTML = \TAS\Core\HTML::InputPassword($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, (isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '30'), (isset($field['maxlength']) ? $field['maxlength'] : '30'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'cb':
                    $HTML = call_user_func($field['function'], $field); // @remark, $field data array is only parameter.

                    break;
                case 'color':
                    $HTML = \TAS\Core\HTML::InputColour($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, (isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
                case 'inputbox':
                case 'varchar':
                case 'string':
                default:
                    $HTML = \TAS\Core\HTML::InputBox($id, isset($field['value']) ? $field['value'] : '', $id, $isrequired, (isset($field['css']) ? $field['css'] : 'form-control'), (isset($field['size']) ? $field['size'] : '30'), (isset($field['maxlength']) ? $field['maxlength'] : '30'), (isset($field['additionalattr']) ? $field['additionalattr'] : ''));
                    break;
            }
            if ($DoLabel) {
                $HTMLLabel = \TAS\Core\HTML::Label((isset($field['label']) ? $field['label'] : $field['Field']), $id, $isrequired);
            } else {
                $HTMLLabel = '';
            }

            if (isset($field['shortnote'])) {
                $HTML .= '<span class="fieldnote field_'.$field['id'].'">'.$field['shortnote'].'</span>';
            }

            $groupName = 'nogroup';
            if (isset($param['Group']) && isset($param['Group'][$field['group']])) {
                $groupName = $field['group'];
            }

            if (isset($field['DoWrapper']) && $field['DoWrapper'] == false) {
                $fieldHTML[$groupName][$fieldname]['html'] = $HTMLLabel.$HTML;
            } else {
                $fieldHTML[$groupName][$fieldname]['html'] = \TAS\Core\HTML::FormField($HTMLLabel, \TAS\Core\HTML::InputWrapper($HTML), (isset($field['wrappertag']) ? $field['wrappertag'] : ''));
            }

            $fieldHTML[$groupName][$fieldname]['displayorder'] = (isset($field['displayorder']) ? $field['displayorder'] : 1);
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
            unset($tmp);
            unset($tmp2);
            foreach ($fieldHTML as $group => $fieldinfo) {
                $sortfield = array();
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
                $sortfield = array();
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
