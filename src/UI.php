<?php

namespace TAS\Core;

class UI
{
    public static function ImageURLFinder($imageObj, $width, $height)
    {
        if (is_array($imageObj['thumbnails']) && count($imageObj['thumbnails']) > 0 && isset($imageObj['thumbnails']['w'.$width.'.h'.$height])) {
            return $imageObj['baseurl'].$imageObj['thumbnails']['w'.$width.'.h'.$height];
        } else {
            return GetResizedImage($imageObj['physicalpath'], $imageObj['url'], $width, $height, $GLOBALS['AppConfig']['NoImage_Listing']);
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

    public static function NumericRangeToDropDown($start, $end, $step, $selectedvalue, $showSelect = true)
    {
        if ($showSelect) {
            $list = '<option value="-1">-- Select --</option>';
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
     * Create HTML Grid for given tagname,.
     *
     * @param array  $SQLQuery
     *                         SQLQuery is an array of three part mainly basicquery/where/orderby, orderby will again be an array
     * @param array  $pages
     *                         is list of various pages in grid, edit/delete/add etc
     * @param string $tagname
     *                         is for storing this grid related data in this tag
     * @param array  $param
     *                         contain other various value, must be an array
     */
    public static function HTMLGridFromRecordSet($SQLQuery, $pages, $tagname, $param = array())
    {
        $listing = '';
        $startpage = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? $_GET['page'] : 1);
        $pagesize = isset($param['pagesize']) ? $param['pagesize'] : $GLOBALS['AppConfig']['PageSize'];
        $start = ($startpage - 1) * $pagesize;

        $orderby = ((isset($_GET['orderby'])) ? $_GET['orderby'] : ((isset($_SESSION[$tagname.'_orderby']) ? $_SESSION[$tagname.'_orderby'] : $param['defaultorder'])));
        $orderdirection = ((isset($_GET['direction'])) ? $_GET['direction'] : ((isset($_SESSION[$tagname.'_direction']) ? $_SESSION[$tagname.'_direction'] : $param['defaultsort'])));
        $_SESSION[$tagname.'_direction'] = $orderdirection;
        $_SESSION[$tagname.'_orderby'] = $orderby;

        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby.' '.$orderdirection)) {
                    $sortstring .= ', '.$val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');

        if (isset($param['nopaging']) && $param['nopaging'] === true) {
            $param['nopaging'] = true;
            $query = $SQLQuery['basicquery'].$SQLQuery['where']." order by $orderby $orderdirection $sortstring ";
        } else {
            if (isset($param['showonly']) && is_numeric($param['showonly'])) {
                $param['nopaging'] = false;
                $query = $SQLQuery['basicquery'].$SQLQuery['where']." order by $orderby $orderdirection $sortstring limit ".(int) $param['showonly'];
            } else {
                $param['nopaging'] = false;
                $query = $SQLQuery['basicquery'].$SQLQuery['where']." order by $orderby $orderdirection $sortstring limit $start, ".$pagesize;
            }
        }

        $filter = '';
        $defaultpage = (!isset($pages['gridpage']) || $pages['gridpage'] == '') ? 'index.php' : $pages['gridpage'];
        $pagingPage = $defaultpage;
        $newdirection = (strtolower($orderdirection) == 'asc') ? 'desc' : 'asc';

        $page = \TAS\Core\Web::AppendQueryString($defaultpage, 'page='.$startpage.'&direction='.$newdirection);
        $corepage = \TAS\Core\Web::AppendQueryString($defaultpage, 'page='.$startpage);

        if (isset($_GET['direction'])) {
            $filter .= '&direction='.$orderdirection;
        }
        if (isset($_GET['orderby'])) {
            $filter .= '&orderby='.$orderby;
        }
        $rs = $GLOBALS['db']->Execute($query);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo $query;
        }
        if ($GLOBALS['db']->RowCount($rs) > 0) {
            $TotalRecordCount = $GLOBALS['db']->ExecuteScalar('Select count(*) from ('.$SQLQuery['basicquery'].$SQLQuery['where'].') t');

            $listing .= '<section class="content-section">
    <div class="container-fluid">
        <div class="row">
            <div class="content-area col-md-12 px-0">
            <div class="col-lg-12 col-md-12 px-0">
              <div class="card">
                <div class="card-body"><h6>'.$TotalRecordCount.' Records </h6>';

            if (isset($param['allowselection']) && $param['allowselection']) {
                $ShowSelection = true;
            } else {
                $ShowSelection = false;
            }
            $totalfield = 1;
            $allowRowSorting = ((isset($param['AllowRowSort']) && $param['AllowRowSort'] == true) ? 'tablesort' : '');
            $listing .= '<div class="table-responsive">
                            <table class="table table-striped '.$allowRowSorting.'"  data-url="'.$defaultpage.'" id="'.((isset($param['tablename'])) ? $param['tablename'] : 'usergrid').'">';
            $listing .= '
			<thead>
				<tr>';
            if ($ShowSelection) {
                $listing .= '<th><input type="checkbox" name="select_'.$tagname.'" id="select_'.$tagname.'" class="checkall"></th>';
                ++$totalfield;
            }

            $RemoveFieldOption = false;

            if ($pages['edit'] == false && $pages['delete'] == false) {
                $RemoveFieldOption = true;
            }
            if (isset($param['extraicons']) && is_array($param['extraicons']) && count($param['extraicons']) > 0) {
                $RemoveFieldOption = false;
            }
            if ($RemoveFieldOption) {
                --$totalfield;
            }
            foreach ($param['fields'] as $field => $val) {
                $sorticon = '';
                if ($orderby == $field || (isset($val['sortstring']) && $orderby == $val['sortstring'])) {
                    if (strtolower($orderdirection) == 'asc') {
                        $sorticon = '<a class="ui-state-default ui-icon-gap ui-corner-all" href="'.$page.'&orderby='.$field.'">
						<i class="fas fa-sort-alpha-up"></i></a>';
                    } else {
                        $sorticon = '<a  class="ui-state-default ui-icon-gap ui-corner-all" href="'.$page.'&orderby='.$field.'">
						<i class="fas fa-sort-alpha-down"></i></a>';
                    }
                }
                $Text = (isset($val['icon']) ? $val['icon'].' ' : '').$val['name'];
                $Label = isset($val['label']) ? $val['label'] : $val['name'];
                switch ($val['type']) {
                    case 'longstring':
                        $count = count($param['fields']);
                        ++$count;
                        $count = (int) ((2 / $count) * 100);
                        $listing .= '<th class="longstring" style="min-width: '.$count.'%;">';
                        break;
                    case 'flag':
                        $listing .= '<th class="flag" style="width: 20px;">';
                        break;
                    case 'currency':
                        $listing .= '<th class="currency">';
                        break;
                    case 'number':
                        $listing .= '<th class="number">';
                        break;
                    default:
                        $listing .= '<th>';
                        break;
                }
                if (isset($param['allowsort']) && $param['allowsort'] === false) {
                    $listing .= $Text;
                } else {
                    $listing .= '<a href="'.$page.'&orderby='.$field.'" title="Sort by '.$Label.'">'.$Text.'</a>'.$sorticon.'</th>';
                }
                ++$totalfield;
            }
            if (!$RemoveFieldOption) {
                $listing .= '	<th><a href="#">Options</a></th>';
            }
            $listing .= '	</tr>';

            if (isset($param['MultiTableSearch']) && $param['MultiTableSearch'] == true) {
                if ($param['nopaging'] == false) {
                    $listing .= '<tr><td class="pager" colspan="'.$totalfield.'">'.\TAS\Core\Utility::Paging($param['tablename'], $pagingPage, $startpage, $SQLQuery['pagingQuery'].$SQLQuery['where'].(isset($SQLQuery['pagingQueryEnd']) ? $SQLQuery['pagingQueryEnd'] : ''), $filter, true, array(
                        'pagesize' => $pagesize,
                    )).'</td></tr>';
                }
            } else {
                if ($param['nopaging'] == false) {
                    $listing .= '<tr><td class="pager"  colspan="'.$totalfield.'">'.\TAS\Core\Utility::Paging($param['tablename'], $pagingPage, $startpage, $SQLQuery['where'], $filter, false, array(
                        'pagesize' => $pagesize,
                    )).'</td></tr>';
                }
            }

            $listing .= '</thead><tbody>';
            $alt = true;
            $total = array();
            while ($row = $GLOBALS['db']->FetchArray($rs)) {
                $option = '';
                if ($GLOBALS['permission']->CheckOperationPermission($tagname, 'edit', $GLOBALS['user']->UserRoleID) && $pages['edit'] !== false) {
                    $option .= '<li><a class="btn btn-icons btn-rounded btn-outline-primary edit" data-toggle="tooltip" title="Edit" href="'.$pages['edit'].'?'.(isset($param['editparamname']) ? $param['editparamname'] : 'id').'='.$row[$param['indexfield']].'"><i class="fas fa-edit"></i></a></li>';
                }
                if ($GLOBALS['permission']->CheckOperationPermission($tagname, 'delete', $GLOBALS['user']->UserRoleID) && $pages['delete'] !== false) {
                    if (is_bool(strstr($pages['delete'], '?')) && strstr($pages['delete'], '?') == false) {
                        $deletelink = $pages['delete'].'?'.(isset($param['deleteparamname']) ? $param['deleteparamname'] : 'delete').'='.$row[$param['indexfield']];
                    } else {
                        $deletelink = $pages['delete'].'&'.(isset($param['deleteparamname']) ? $param['deleteparamname'] : 'delete').'='.$row[$param['indexfield']];
                    }
                    $option .= '<li><a class="btn btn-icons btn-rounded btn-outline-danger delete" data-toggle="tooltip" title="Delete" href="'.$deletelink.'" '.(isset($param['deletecustommessage']) ? 'data-custommessage="'.\TAS\Core\TemplateHandler::PrepareContent($param['deletecustommessage'], array(
                        'id' => $row[$param['indexfield']],
                    )) : '').'" '.'><i class="fas fa-trash-alt"></i></a></li>';
                }

                if (isset($param['extraicons']) && is_array($param['extraicons'])) {
                    foreach ($param['extraicons'] as $icon) {
                        if (isset($icon['removeOnRowCondition']) && $icon['removeOnRowCondition'] == true && $IsRowCondition == true) {
                            continue;
                        }
                        $IconHTML = '<li><a class="btn btn-icons btn-rounded btn-outline-fa-color '.$icon['tagname'].'" data-toggle="tooltip" title="'.$icon['tooltip'].'" data-value="'.$row[(isset($icon['indexfield']) ? $icon['indexfield'] : $param['indexfield'])].'" href="'.$icon['link'].(isset($icon['paramname']) ? '?'.$icon['paramname'].'=' : '?id=').$row[(isset($icon['indexfield']) ? $icon['indexfield'] : $param['indexfield'])].'"><i class="fas '.$icon['iconclass'].'"></i></a></li>';
                        $option .= $IconHTML;
                    }
                }

                $additionalClass = '';
                if (isset($param['rowcondition']) && is_array($param['rowcondition'])) {
                    if ($row[$param['rowcondition']['column']] == $param['rowcondition']['onvalue']) {
                        $additionalClass = $param['rowcondition']['cssclass'];
                    } else {
                        $additionalClass = '';
                    }
                } else {
                    $additionalClass = '';
                }

                if (isset($param['rowconditioncb']) && $param['rowconditioncb'] != '' && function_exists($param['rowconditioncb'])) {
                    /**
                     * Call Back function Definiation is function xyz ($row, $additionalClass) { }.
                     */
                    $additionalClass = call_user_func($param['rowconditioncb'], $row, $additionalClass);
                }

                $listing .= "\n".'<tr data-id="'.$row[$param['indexfield']].'" id="row_'.$row[$param['indexfield']].'"  class="griddatarow '.(($alt) ? 'gridrow' : 'altgridrow').' '.$additionalClass.'">';

                if ($ShowSelection) {
                    $listing .= '<td><input type="checkbox" name="select_'.$tagname.'_'.$row[$param['indexfield']].'" id="select_'.$tagname.'_'.$row[$param['indexfield']].'" class="checkall_child"></td>';
                }
                $fieldCounter = 0;
                foreach ($param['fields'] as $field => $val) {
                    $fielddata = '';
                    $cssClass = '';
                    switch ($val['type']) {
                        case 'globalarray':
                            if (isset($val['arrayname'])) {
                                $fielddata = $GLOBALS[$val['arrayname']][$row[$field]];
                            } else {
                                $fielddata = $GLOBALS[$field][$row[$field]];
                            }
                            break;
                        case 'string':

                            if (isset($val['length']) && is_numeric($val['length']) && (int) $val['length'] > 0) {
                                $fielddata = '<span title="'.htmlentities($row[$field]).'">'.substr($row[$field], 0, $val['length']).'</span>';
                            } else {
                                $fielddata = $row[$field];
                            }
                            break;
                        case 'longstring':
                            $fielddata = $row[$field];
                            break;
                        case 'onoff':
                            $fielddata = (((int) $row[$field] === 1 || strtolower($row[$field]) === 'active' || $row[$field] === true || strtolower($row[$field]) === 'yes') ? 'Yes' : 'No');

                            if (isset($val['mode']) && $val['mode'] == 'fa') {
                                if ($fielddata == 'Yes') {
                                    $fielddata = ' <i class="fas '.(isset($val['iconyes']) ? $val['iconyes'] : 'fa-heart').' green" aria-hidden="true"></i>';
                                } else {
                                    $fielddata = ' <i class="fas '.(isset($val['iconno']) ? $val['iconno'] : 'fa-heart').' red" aria-hidden="true"></i>';
                                }
                            } else {
                                if ($fielddata == 'Yes' && isset($val['iconyes'])) {
                                    $fielddata = '<img src="'.$val['iconyes'].'" class="gridimage '.$field.'">';
                                }
                                if ($fielddata == 'No' && isset($val['iconno'])) {
                                    $fielddata = '<img src="'.$val['iconno'].'" class="gridimage '.$field.'">';
                                }
                            }
                            $fielddata = '<a href="'.$corepage.'&id='.$row[$param['indexfield']].'&type='.$field.'" class="'.$field.' gridinnerlink">'.$fielddata.'</a>';
                            $cssClass = 'gridtable-onoff';
                            break;
                        case 'flag':
                            $fielddata = (($row[$field] == 1 || strtolower($row[$field]) == 'active' || $row[$field] == true || strtolower($row[$field]) == 'yes') ? 'Yes' : 'No');
                            if ($fielddata == 'Yes') {
                                $fielddata = '<img src="'.(isset($val['icon']) ? $val['icon'] : '{HomeURL}/theme/images/flag.png').'" class="gridimage flag '.$field.'">';
                            } else {
                                $fielddata = '';
                            }
                                $fielddata = '<a href="'.$corepage.'&id='.$row[$param['indexfield']].'&type='.$field.'" class="'.$field.' gridinnerlink">'.$fielddata.'</a>';
                                $cssClass = 'gridtable-flag';
                                break;
                        case 'phone':
                            if ($row[$field] != '') {
                                $fielddata = \TAS\Core\DataFormat::FormatPhone($row[$field], $val['PhoneLength'] ?? 10);
                            } else {
                                $fielddata = $row[$field];
                            }
                            break;
                        case 'date':
                            $fielddata = \TAS\Core\DataFormat::DBToDateFormat($row[$field]);
                            break;
                        case 'datetime':
                            $row[$field];
                            $fielddata = \TAS\Core\DataFormat::DBToDateTimeFormat($row[$field]);
                            break;
                        case 'currency':
                            $cssClass = 'gridtable-currency';
                            if (isset($val['postsymbol']) && $val['postsymbol']) {
                                $fielddata = number_format(floatval($row[$field]), 2).$val['postsymbol'];
                            } else {
                                $fielddata = $GLOBALS['AppConfig']['Currency'].number_format(floatval($row[$field]), 2);
                            }
                            if (isset($param['TotalDisplay']) && $param['TotalDisplay'] == true) {
                                $total[$field] = (isset($total[$field]) ? $total[$field] : 0.0);
                                $total[$field] += floatval($row[$field]);
                            }
                            break;
                        case 'numeric':
                        case 'number':
                            $cssClass = 'gridtable-currency';
                            if (isset($val['number-decimal']) && $val['number-decimal']) {
                                $fielddata = number_format(floatval($row[$field]), 2);
                            } else {
                                $fielddata = (int) round(floatval($row[$field]), 4);
                            }

                            break;
                        case 'cb':
                        case 'callback':
                            $fielddata = call_user_func($val['function'], $row, $field); // @remark, $field data array is only parameter.
                            break;
                        case 'image':
                            $fielddata = '<img src="'.$row[$field].'" class="thumbnailsize">';
                            break;
                        case 'color':
                            $fielddata = '<div class="colordiv" style="background:'.$row[$field].';"></div>';
                            break;
                            // case 'json' :
                            // $fielddata = json_decode($row[$field]) ;
                            // break;
                        default:
                            $fielddata = $row[$field];
                            break;
                    }

                    $listing .= "\r\n \t";
                    if ((isset($param['LinkFirstColumn']) && $param['LinkFirstColumn'] == true && $fieldCounter == 0) && isset($pages['view']) && $pages['view'] !== false) {
                        $indexID = isset($param['LinkIndexField']) ? 'LinkIndexField' : 'indexfield';
                        $listing .= '<td class="'.$cssClass.'"><a class="viewlink" href="'.$pages['view'].'?'.(isset($param['viewparamname']) ? $param['viewparamname'] : 'id').'='.$row[$param[$indexID]].'">'.$fielddata.'</a></td>';
                    } elseif (isset($param['LinkAllColumn']) && $param['LinkAllColumn'] == true && isset($pages['view']) && $pages['view'] !== false) {
                        $indexID = isset($param['LinkIndexField']) ? 'LinkIndexField' : 'indexfield';
                        $listing .= '<td class="'.$cssClass.'"><a class="viewlink" href="'.$pages['view'].'?'.(isset($param['viewparamname']) ? $param['viewparamname'] : 'id').'='.$row[$param[$indexID]].'">'.$fielddata.'</a></td>';
                    } else {
                        if (isset($val['link'])) {
                            $linkColumn = str_replace('{1}', $row[$val['linkfield']], $val['link']);
                            $listing .= '<td class="'.$cssClass.'"><a href="'.$linkColumn.'">'.$fielddata.'</a></td>';
                        } else {
                            $listing .= '<td class="'.$cssClass.'">'.$fielddata.'</td>';
                        }
                    }
                    ++$fieldCounter;
                }
                if (!$RemoveFieldOption) {
                    $listing .= '<td class="gridtable-optionrow"><ul class="table-ul">'.$option.'</ul></td></tr>';
                }
                $alt = !($alt);
            }

            if (isset($param['TotalDisplay']) && $param['TotalDisplay'] == true) {
                $listing .= '<tr class="total">';
                foreach ($param['fields'] as $field => $val) {
                    switch ($val['type']) {
                        case 'currency':
                            $listing .= '<td class="currency">'.$GLOBALS['AppConfig']['Currency'].number_format($total[$field], 2).'</td>';
                            break;
                        case 'number':
                            $listing .= '<td class="number">'.$GLOBALS['AppConfig']['Currency'].number_format($total[$field], 2).'</td>';
                            break;
                        default:
                            $listing .= '<td>&nbsp;</td>';
                            break;
                    }
                }
                $listing .= '</tr>';
            }

            if (isset($param['MultiTableSearch']) && $param['MultiTableSearch'] == true) {
                $listing .= '</tbody>';
                if ($param['nopaging'] == false) {
                    $listing .= '<tfoot><tr><td class="pager" colspan="'.$totalfield.'">'.\TAS\Core\Utility::Paging($param['tablename'], $pagingPage, $startpage, $SQLQuery['pagingQuery'].$SQLQuery['where'].(isset($SQLQuery['pagingQueryEnd']) ? $SQLQuery['pagingQueryEnd'] : ''), $filter, true, array(
                        'pagesize' => $pagesize,
                    )).'</td></tr></tfoot>';
                }
                $listing .= '</table>
                                </div>
                                </div>
                                </div>
                            </div>
                          </div>
                         </div>
                         </section>';
            } else {
                $listing .= '</tbody>';
                if ($param['nopaging'] == false) {
                    $listing .= '<tfoot><tr><td class="pager" colspan="'.$totalfield.'">'.\TAS\Core\Utility::Paging($param['tablename'], $pagingPage, $startpage, $SQLQuery['where'], $filter, false, array(
                        'pagesize' => $pagesize,
                    )).'</td></tr></tfoot>';
                }
                $listing .= '</table></div>
                  </div>
                </div>
            </div>
          </div>
         </div>
         </section>';
            }
        } else {
            $listing = '<section class="content-section">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="content-area col-md-12 px-0">
                                <div class="col-lg-12 col-md-12 px-0">
                                  <div class="card">
                                    <div class="card-body"><h6>No '.ucwords($tagname).' information is available ... </h6>
                                    </div>
                                  </div>
                                </div>
                            </div>
                          </div>
                         </div>
                         </section>';
        }

        return $listing;
    }

    /**
     * Display the Grid of Given Query.
     * This do not validate any action button, and do not show Options by default,.
     *
     * @param unknown_type $SQLQuery
     * @param unknown_type $pages
     * @param unknown_type $tagname
     * @param unknown_type $param
     */
    public static function HTMLGridForPublic($SQLQuery, $pages, $tagname, $param = array())
    {
        $listing = '';
        $startpage = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? $_GET['page'] : 1);
        $pagesize = isset($param['pagesize']) ? $param['pagesize'] : $GLOBALS['AppConfig']['PageSize'];

        $start = ($startpage - 1) * $pagesize;

        $param['defaultorder'] = isset($param['defaultorder']) ? $param['defaultorder'] : '';
        $param['defaultsort'] = isset($param['defaultsort']) ? $param['defaultsort'] : '';

        $orderby = ((isset($_GET['orderby'])) ? $_GET['orderby'] : ((isset($_SESSION[$tagname.'_orderby']) ? $_SESSION[$tagname.'_orderby'] : $param['defaultorder'])));
        $orderdirection = ((isset($_GET['direction'])) ? $_GET['direction'] : ((isset($_SESSION[$tagname.'_direction']) ? $_SESSION[$tagname.'_direction'] : $param['defaultsort'])));
        $_SESSION[$tagname.'_direction'] = $orderdirection;
        $_SESSION[$tagname.'_orderby'] = $orderby;

        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby.' '.$orderdirection)) {
                    $sortstring .= ', '.$val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');

        $orderLine = '';
        if (isset($param['noorder']) && $param['noorder'] == true) {
            $orderLine = ' ';
        } else {
            $orderLine = " order by $orderby $orderdirection $sortstring ";
        }

        if (isset($param['nopaging']) && $param['nopaging'] === true) {
            $param['nopaging'] = true;
            $query = $SQLQuery['basicquery'].$SQLQuery['where'].$orderLine;
        } else {
            $param['nopaging'] = false;
            $query = $SQLQuery['basicquery'].$SQLQuery['where'].$orderLine." limit $start, ".$pagesize;
        }

        $filter = '';

        $defaultpage = (!isset($pages['gridpage']) || $pages['gridpage'] == '') ? 'index.php' : $pages['gridpage'];
        $pagingPage = $defaultpage;
        $newdirection = (strtolower($orderdirection) == 'asc') ? 'desc' : 'asc';

        $page = \TAS\Core\Web::AppendQueryString($defaultpage, 'page='.$startpage.'&direction='.$newdirection);
        $corepage = \TAS\Core\Web::AppendQueryString($defaultpage, 'page='.$startpage);

        if (isset($_GET['direction'])) {
            $filter .= '&direction='.$orderdirection;
        }
        if (isset($_GET['orderby'])) {
            $filter .= '&orderby='.$orderby;
        }
        $rs = $GLOBALS['db']->Execute($query);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo $query;
        }
        if ($GLOBALS['db']->RowCount($rs) > 0) {
            $TotalRecordCount = $GLOBALS['db']->ExecuteScalar('Select count(*) from ('.$SQLQuery['basicquery'].$SQLQuery['where'].') t');

            $recordText = isset($param['totalRecordText']) ? $param['totalRecordText'] : ' Records';
            $listing .= '<section class="content-section">
    <div class="container-fluid">
        <div class="row">
            <div class="content-area col-md-12 px-0">
            <div class="col-lg-12 col-md-12 px-0">
              <div class="card">
                <div class="card-body"><h6>'.$TotalRecordCount.' '.$recordText.' </h6>';

            if (isset($param['allowselection']) && $param['allowselection']) {
                $ShowSelection = true;
            } else {
                $ShowSelection = false;
            }
            $totalfield = 1;

            $allowRowSorting = ((isset($param['AllowRowSort']) && $param['AllowRowSort'] == true) ? 'tablesort' : '');
            $listing .= '<div class="table-responsive">
                            <table class="table table-striped '.$allowRowSorting.'" data-url="'.$defaultpage.'" id="'.((isset($param['tablename'])) ? $param['tablename'] : 'usergrid').'">';

            $listing .= '<thead>
				<tr>';
            if ($ShowSelection) {
                $listing .= '<th style="width: 20px"><input type="checkbox" name="select_'.$tagname.'" id="select_'.$tagname.'" class="checkall"></th>';
                ++$totalfield;
            }
            reset($param['fields']);
            foreach ($param['fields'] as $field => $val) {
                $sorticon = '';
                if ($orderby == $field || (isset($val['sortstring']) && $orderby == $val['sortstring'])) {
                    if (strtolower($orderdirection) == 'asc') {
                        $sorticon = '<a style="float:left" class="ui-state-default ui-icon-gap ui-corner-all" href="'.$page.'&orderby='.$field.'">
						<span class="ui-icon ui-icon-circle-triangle-n"></span></a>';
                    } else {
                        $sorticon = '<a style="float:left" class="ui-state-default ui-icon-gap ui-corner-all" href="'.$page.'&orderby='.$field.'">
						<span class="ui-icon ui-icon-circle-triangle-s"></span></a>';
                    }
                }
                $Text = (isset($val['icon']) ? $val['icon'].' ' : '').$val['name'];
                $Label = isset($val['label']) ? $val['label'] : $val['name'];

                switch ($val['type']) {
                    case 'longstring':
                        $count = count($param['fields']);
                        ++$count;
                        $count = (int) ((2 / $count) * 100);
                        $listing .= '<th style="width:'.$count.'%;">';
                        break;
                    case 'flag':
                        $listing .= '<th style="width: 20px;">';
                        break;
                    case 'currency':
                        $listing .= '<th class="currency">';
                        break;
                    case 'number':
                        $listing .= '<th class="number">';
                        break;
                    default:
                        $listing .= '<th>';
                        break;
                }
                if (isset($param['allowsort']) && $param['allowsort'] === false) {
                    $listing .= $Text;
                } else {
                    $listing .= '<a href="'.$page.'&orderby='.$field.'" title="Sort by '.$Label.'">'.$Text.'</a>'.$sorticon.'</th>';
                }
                ++$totalfield;
            }

            $DoOption = ($pages['edit'] !== false || $pages['delete'] !== false || (isset($param['extraicons']) && count($param['extraicons']) > 0)) ? true : false;

            if ($DoOption) {
                $listing .= '	<th><a href="#">Options</a></th>';
            }

            $listing .= '</tr></thead><tbody>';
            $alt = true;

            while ($row = $GLOBALS['db']->FetchArray($rs)) {
                $option = '';
                if ($pages['edit'] !== false) {
                    $option .= '<li><a class="edit btn btn-icons btn-rounded btn-outline-primary" data-toggle="tooltip" title="Edit" href="'.$pages['edit'].'?'.(isset($param['editparamname']) ? $param['editparamname'] : 'id').'='.$row[$param['indexfield']].'"><span class="ui-icon ui-icon-pencil" ></span><i class="fas fa-edit"></i></a></li>';
                }
                if ($pages['delete'] !== false) {
                    if (is_bool(strstr($pages['delete'], '?')) && strstr($pages['delete'], '?') == false) {
                        $deletelink = $pages['delete'].'?'.(isset($param['deleteparamname']) ? $param['deleteparamname'] : 'delete').'='.$row[$param['indexfield']];
                    } else {
                        $deletelink = $pages['delete'].'&'.(isset($param['deleteparamname']) ? $param['deleteparamname'] : 'delete').'='.$row[$param['indexfield']];
                    }
                    $option .= '<li><a class="btn btn-icons btn-rounded btn-outline-danger delete" data-toggle="tooltip" title="Delete" href="'.$deletelink.'" '.(isset($param['deletecustommessage']) ? 'data-custommessage="'.PrepareContent($param['deletecustommessage'], array(
                            'id' => $row[$param['indexfield']],
                        )) : '').'" '.'><i class="fas fa-trash"></i></a></li>';
                }
                if (isset($param['extraicons']) && is_array($param['extraicons'])) {
                    foreach ($param['extraicons'] as $icon) {
                        $link = \TAS\Core\Web::AppendQueryString($icon['link'], (isset($icon['paramname']) ? $icon['paramname'].'=' : 'id=').$row[$param['indexfield']]);
                        $option .= '<li><a class="btn btn-icons btn-rounded btn-outline-fa-color '.$icon['tagname'].'" data-toggle="tooltip" title="'.$icon['tooltip'].'"  href="'.$link.'"><i class="fas '.$icon['iconclass'].'"></i></a></li>';
                    }
                }

                if (isset($param['rowcondition']) && is_array($param['rowcondition'])) {
                    if ($row[$param['rowcondition']['column']] == $param['rowcondition']['onvalue']) {
                        $listing .= "\n".'<tr class="'.(($alt) ? 'gridrow' : 'altgridrow').' '.$param['rowcondition']['cssclass'].'">';
                    } else {
                        $listing .= "\n".'<tr class="'.(($alt) ? 'gridrow' : 'altgridrow').'">';
                    }
                } else {
                    $listing .= "\n".'<tr data-id="'.$row[$param['indexfield']].'" id="row_'.$row[$param['indexfield']].'"  class="griddatarow '.(($alt) ? 'gridrow' : 'altgridrow').'">';
                }

                if ($ShowSelection) {
                    $listing .= '<td><input type="checkbox" name="select_'.$tagname.'['.$row[$param['indexfield']].']" id="select_'.$tagname.'_'.$row[$param['indexfield']].'" class="checkall_child"></td>';
                }
                $fieldCounter = 0;
                reset($param['fields']);
                foreach ($param['fields'] as $field => $val) {
                    $fielddata = '';
                    $cssClass = '';
                    switch ($val['type']) {
                            case 'globalarray':
                                if (isset($val['arrayname'])) {
                                    $fielddata = $GLOBALS[$val['arrayname']][$row[$field]];
                                } else {
                                    $fielddata = $GLOBALS[$field][$row[$field]];
                                }
                                break;
                            case 'string':
                                $fielddata = $row[$field];
                                break;
                            case 'longstring':
                                $fielddata = $row[$field];
                                break;
                            case 'onoff':
                                $fielddata = (((int) $row[$field] === 1 || strtolower($row[$field]) === 'active' || $row[$field] === true || strtolower($row[$field]) === 'yes') ? 'Yes' : 'No');
                                if ($fielddata == 'Yes' && isset($val['iconyes'])) {
                                    $fielddata = '<img src="'.$val['iconyes'].'" class="gridimage '.$field.'">';
                                }
                                    if ($fielddata == 'No' && isset($val['iconno'])) {
                                        $fielddata = '<img src="'.$val['iconno'].'" class="gridimage '.$field.'">';
                                    }
                                        $fielddata = '<a href="'.$corepage.'&id='.$row[$param['indexfield']].'&type='.$field.'" class="'.$field.' gridinnerlink">'.$fielddata.'</a>';
                                        $cssClass = 'gridtable-onoff';
                                        break;
                            case 'flag':
                                $fielddata = (($row[$field] == 1 || strtolower($row[$field]) == 'active' || $row[$field] == true || strtolower($row[$field]) == 'yes') ? 'Yes' : 'No');
                                if ($fielddata == 'Yes') {
                                    $fielddata = '<img src="'.(isset($val['icon']) ? $val['icon'] : '{HomeURL}/theme/images/flag.png').'" class="gridimage flag '.$field.'">';
                                } else {
                                    $fielddata = '';
                                }
                                    $fielddata = '<a href="'.$corepage.'&id='.$row[$param['indexfield']].'&type='.$field.'" class="'.$field.' gridinnerlink">'.$fielddata.'</a>';
                                    $cssClass = 'gridtable-flag';
                                    break;
                            case 'phone':
                                if ($row[$field] != '') {
                                    $fielddata = \TAS\Core\DataFormat::FormatPhone($row[$field], $val['PhoneLength'] ?? 10);
                                } else {
                                    $fielddata = $row[$field];
                                }
                                break;
                            case 'date':
                                $fielddata = \TAS\Core\DataFormat::DBToDateFormat($row[$field], (isset($param['dateformat']) ? $param['dateformat'] : 'm-d-Y'));
                                break;
                            case 'datetime':
                                $row[$field];
                                $fielddata = \TAS\Core\DataFormat::DBToDateTimeFormat($row[$field], (isset($param['datetimeformat']) ? $param['datetimeformat'] : 'm/d/Y H:i a'));
                                break;
                            case 'currency':
                                $cssClass = 'gridtable-currency';
                                if (isset($val['postsymbol']) && $val['postsymbol']) {
                                    $fielddata = number_format(floatval($row[$field]), 2).$val['postsymbol'];
                                } else {
                                    $fielddata = $GLOBALS['AppConfig']['Currency'].number_format(floatval($row[$field]), 2);
                                }
                                break;
                            case 'numeric':
                            case 'number':
                                $cssClass = 'gridtable-currency';
                                if (isset($val['number-decimal']) && $val['number-decimal']) {
                                    $fielddata = number_format(floatval($row[$field]), 2);
                                } else {
                                    $fielddata = (int) round(floatval($row[$field]), 4);
                                }

                                break;
                            case 'cb':
                            case 'callback':
                                $fielddata = call_user_func($val['function'], $row, $field); // @remark, $field data array is only parameter.
                                break;
                            case 'image':
                                $fielddata = '<img src="'.(isset($val['prefixUrl']) ? $val['prefixUrl'] : '').$row[$field].'" class="thumbnailsize">';
                                break;
                            case 'color':
                                $fielddata = '<div class="colordiv" style="background:'.$row[$field].';"></div>';
                                break;
                            default:
                                $fielddata = $row[$field];
                                break;
                        }

                    $listing .= "\r\n \t";
                    if ((isset($param['LinkFirstColumn']) && $param['LinkFirstColumn'] == true && $fieldCounter == 0) && isset($pages['view']) && $pages['view'] !== false) {
                        $indexID = isset($param['LinkIndexField']) ? 'LinkIndexField' : 'indexfield';
                        $listing .= '<td class="'.$cssClass.'"><a class="viewlink" href="'.$pages['view'].'?'.(isset($param['viewparamname']) ? $param['viewparamname'] : 'id').'='.$row[$param[$indexID]].'">'.$fielddata.'</a></td>';
                    } elseif (isset($param['LinkAllColumn']) && $param['LinkAllColumn'] == true && isset($pages['view']) && $pages['view'] !== false) {
                        $indexID = isset($param['LinkIndexField']) ? 'LinkIndexField' : 'indexfield';
                        $listing .= '<td class="'.$cssClass.'"><a class="viewlink" href="'.$pages['view'].'?'.(isset($param['viewparamname']) ? $param['viewparamname'] : 'id').'='.$row[$param[$indexID]].'">'.$fielddata.'</a></td>';
                    } else {
                        if (isset($val['link'])) {
                            $linkColumn = str_replace('{1}', $row[$val['linkfield']], $val['link']);
                            $listing .= '<td class="'.$cssClass.'"><a href="'.$linkColumn.'">'.$fielddata.'</a></td>';
                        } else {
                            $listing .= '<td class="'.$cssClass.'">'.$fielddata.'</td>';
                        }
                    }
                    ++$fieldCounter;
                }

                if ($DoOption) {
                    $listing .= '<td class="gridtable-optionrow"><ul class="table-ul">'.$option.'</ul></td></tr>';
                }
                $alt = !($alt);
            }
            if (!$DoOption) {
                --$totalfield;
            } // Reduce counter as option is not shown

            if (isset($param['MultiTableSearch']) && $param['MultiTableSearch'] == true) {
                $listing .= '</tbody>';
                if ($param['nopaging'] == false) {
                    $listing .= '<tfoot><tr><td class="pager" colspan="'.$totalfield.'">'.\TAS\Core\Utility::Paging($param['tablename'], $pagingPage, $startpage, $SQLQuery['pagingQuery'].$SQLQuery['where'], $filter, true, array(
                                'pagesize' => $pagesize,
                            )).'</td></tr></tfoot>';
                }
                $listing .= '</table></div>
                  </div>
                </div>
            </div>
          </div>
         </div>
         </section>';
            } else {
                $listing .= '</tbody>';
                if ($param['nopaging'] == false) {
                    $listing .= '<tfoot><tr><td class="pager" colspan="'.$totalfield.'">'.\TAS\Core\Utility::Paging($param['tablename'], $pagingPage, $startpage, $SQLQuery['where'], $filter, false, array(
                                'pagesize' => $pagesize,
                            )).'</td></tr></tfoot>';
                }
                $listing .= '</table></div>
                  </div>
                </div>
            </div>
          </div>
         </div>
         </section>';
            }
        } else {
            $listing = '<section class="content-section">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="content-area col-md-12 px-0">
                                <div class="col-lg-12 col-md-12 px-0">
                                  <div class="card">
                                    <div class="card-body"><h6>No '.ucwords($tagname).' information is available ... </h6>
                                    </div>
                                  </div>
                                </div>
                            </div>
                          </div>
                         </div>
                      </section>';
        }

        return $listing;
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
