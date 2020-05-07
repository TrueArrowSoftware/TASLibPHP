<?php

namespace TAS\Core;

class Grid
{
    public $QueryOptions;

    public $Options;

    public function __construct($options = null, $queryoptions = null)
    {
        $this->QueryOptions = ($queryoptions == null ? Grid::DefaultQueryOptions() : $queryoptions);
        $this->Options = ($options == null ? Grid::DefaultOptions() : $options);
    }

    /**
     * Return the default options.
     *
     * @return array
     */
    public static function DefaultOptions(): array
    {
        return [
            'gridurl' => '',
            'gridid' => 'mygrid',
            'tagname' => 'grid',
            'useget' => true, //Not used as of now, might be used in future to disable Values from GET.
            'pagesize' => 50,
            'allowsorting' => true,
            'allowpaging' => true,
            'showtotalrecord' => true,
            'totalrecordtext' => '{totalrecord} Records',
            'allowselection' => false,
            'roworder' => false,
            'fields' => array(),
            'option' => array(), //extraicons
            'rowconditioncallback' => array(),
            'dateformat' => 'm/d/Y',
            'datetimeformat' => 'm/d/Y H:i:a',
            'norecordtext' => 'No record found',
            'currentpage' => 1,
        ];
    }

    public static function DefaultQueryOptions(): array
    {
        return [
            'defaultorderby' => '',
            'defaultsortdirection' => '',
            'whereconditions' => '',
            'basicquery' => '',
            'pagingquery' => '',
            'pagingqueryend' => '',
            'indexfield' => '',
            'orderby' => array(),
            'noorderby' => false, //in case your query has it.
            'recordshowlimit' => 0,
            'tablename' => '',
        ];
    }

    public function DefaultIcon(): array
    {
        return [
            'edit' => [
                'link' => $this->Options['gridurl'],
                'iconclass' => 'fa-edit',
                'tooltip' => 'edit this record',
                'tagname' => 'edit',
                'paramname' => 'id',
            ],
            'delete' => [
                'link' => $this->Options['gridurl'],
                'iconclass' => 'fa-trash',
                'tooltip' => 'delete this record',
                'tagname' => 'delete btn-outline-danger',
                'paramname' => 'delete',
            ],
        ];
    }

    public function Render(): string
    {
        $listing = '';
        $startpage = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int) $_GET['page'] : (int) $this->Options['currentpage']);
        $pagesize = isset($this->Options['pagesize']) ? $this->Options['pagesize'] : $GLOBALS['AppConfig']['PageSize'];

        $start = ($startpage - 1) * $pagesize;

        $this->QueryOptions['defaultorderby'] = isset($this->QueryOptions['defaultorderby']) ? $this->QueryOptions['defaultorderby'] : '';
        $this->QueryOptions['defaultsortdirection'] = isset($this->QueryOptions['defaultsortdirection']) ? $this->QueryOptions['defaultsortdirection'] : '';

        $orderby = (isset($_GET['ob']) ? $_GET['ob'] : (isset($_SESSION[$this->Options['gridid'].$this->Options['tagname'].'_ob']) ? $_SESSION[$this->Options['gridid'].$this->Options['tagname'].'_ob'] : $this->QueryOptions['defaultorderby']));
        $orderdirection = ((isset($_GET['d'])) ? $_GET['d'] : ((isset($_SESSION[$this->Options['gridid'].$this->Options['tagname'].'_d']) ? $_SESSION[$this->Options['gridid'].$this->Options['tagname'].'_d'] : $this->QueryOptions['defaultsortdirection'])));

        $_SESSION[$this->Options['gridid'].$this->Options['tagname'].'_d'] = $orderdirection;
        $_SESSION[$this->Options['gridid'].$this->Options['tagname'].'_ob'] = $orderby;

        $sortstring = '';
        if (isset($this->QueryOptions['orderby']) && is_array($this->QueryOptions['orderby'])) {
            foreach ($this->QueryOptions['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (trim($tmpsplit[0]) != $orderby) {
                    $sortstring .= ', '.$val;
                }
            }
            $sortstring = trim($sortstring, ',');
        }

        $orderLine = '';
        if (isset($this->Options['noorderby']) && $this->Options['noorderby'] == true) {
            $orderLine = ' ';
        } else {
            $orderLine = " order by $orderby $orderdirection $sortstring ";
        }

        if (isset($this->Options['allowpaging']) && $this->Options['allowpaging'] === false) {
            $query = $this->QueryOptions['basicquery'].$this->QueryOptions['whereconditions'].$orderLine;
            if (isset($this->QueryOptions['recordshowlimit']) && is_numeric($this->QueryOptions['recordshowlimit']) && (int) $this->QueryOptions['recordshowlimit'] > 0) {
                $query = ' limit '.(int) $this->QueryOptions['recordshowlimit'];
            }
        } else {
            $query = $this->QueryOptions['basicquery'].$this->QueryOptions['whereconditions'].$orderLine." limit $start, ".$pagesize;
        }

        $filter = '';

        $defaultpage = (!isset($this->Options['gridurl']) || $this->Options['gridurl'] == '') ? 'index.php' : $this->Options['gridurl'];
        $pagingPage = $defaultpage;
        $newdirection = (strtolower($orderdirection) == 'asc') ? 'desc' : 'asc';

        $page = \TAS\Core\Web::AppendQueryString($defaultpage, 'page='.$startpage.'&d='.$newdirection);
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
            $TotalRecordCount = $GLOBALS['db']->ExecuteScalar('Select count(*) from ('.$this->QueryOptions['basicquery'].$this->QueryOptions['whereconditions'].') t');

            $recordText = isset($this->Options['totalRecordText']) ? $this->Options['totalRecordText'] : '{totalrecord} records';
            $recordText = str_replace('{totalrecord}', $TotalRecordCount, $recordText);

            $listing .= '<section class="content-section">
    <div class="container-fluid">
        <div class="row">
            <div class="content-area col-md-12 px-0">
            <div class="col-lg-12 col-md-12 px-0">
              <div class="card">
                <div class="card-body">';
            if ($this->Options['showtotalrecord']) {
                $listing .= '<h6>'.$recordText.' </h6>';
            }

            if (isset($this->Options['allowselection']) && $this->Options['allowselection']) {
                $ShowSelection = true;
            } else {
                $ShowSelection = false;
            }
            $totalfield = 1;

            $allowRowSorting = ((isset($this->Options['roworder']) && $this->Options['roworder'] == true) ? 'tablesort' : '');
            $listing .= '<div class="table-responsive">
                            <table class="table table-striped '.$allowRowSorting.'" data-url="'.$defaultpage.'" id="'.((isset($this->Options['tablename'])) ? $this->Options['tablename'] : 'usergrid').'">';
            $listing .= '<thead>
				<tr>';
            if ($ShowSelection) {
                $listing .= '<th style="width: 20px"><input type="checkbox" name="select_'.$this->Options['tagname'].'" id="select_'.$this->Options['tagname'].'" class="checkall"></th>';
                ++$totalfield;
            }

            $RemoveFieldOption = false;
            if (isset($this->Options['option']) && is_array($this->Options['option']) && count($this->Options['option']) == 0) {
                $RemoveFieldOption = true;
                --$totalfield;
            }

            reset($this->Options['fields']);

            foreach ($this->Options['fields'] as $field => $val) {
                $sorticon = '';
                if ($orderby == $field || (isset($val['sortstring']) && $orderby == $val['sortstring'])) {
                    if (strtolower($orderdirection) == 'asc') {
                        $sorticon = '<a class="ui-state-default ui-icon-gap ui-corner-all" href="'.$page.'&ob='.$field.'">
						<i class="fas fa-sort-alpha-up"></i></a>';
                    } else {
                        $sorticon = '<a  class="ui-state-default ui-icon-gap ui-corner-all" href="'.$page.'&ob='.$field.'">
						<i class="fas fa-sort-alpha-down"></i></a>';
                    }
                }
                $Text = (isset($val['icon']) ? $val['icon'].' ' : '').$val['name'];
                $Label = isset($val['label']) ? $val['label'] : $val['name'];

                switch ($val['type']) {
                    case 'longstring':
                        $count = count($this->Options['fields']);
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
                if (isset($this->Options['allowsorting']) && $this->Options['allowsorting'] === false) {
                    $listing .= $Text;
                } else {
                    $listing .= '<a href="'.$page.'&ob='.$field.'" title="Sort by '.$Label.'">'.$Text.'</a>'.$sorticon.'</th>';
                }
                ++$totalfield;
            }

            if (!$RemoveFieldOption) {
                $listing .= '	<th><a href="#">Options</a></th>';
            }
            $listing .= '</tr>';

            if ($this->Options['allowpaging']) {
                $pquery = isset($this->QueryOptions['pagingquery']) ? $this->QueryOptions['pagingquery'] : $this->QueryOptions['basicquery'];
                $pageingrow = '<tr><td class="pager" colspan="'.$totalfield.'">'.
                    \TAS\Core\Utility::Paging($this->QueryOptions['tablename'], $pagingPage, $startpage, $pquery.$this->QueryOptions['whereconditions'].(isset($this->QueryOptions['pagingqueryend']) ? $this->QueryOptions['pagingqueryend'] : ''), $filter, true,
                            array(
                    'pagesize' => $pagesize,
                )).'</td></tr>';

                $listing .= $pageingrow;
            }

            $listing .= '</thead><tbody>';

            $alt = true;

            while ($row = $GLOBALS['db']->FetchArray($rs)) {
                $option = '';
                if (isset($this->Options['option']) && is_array($this->Options['option'])) {
                    foreach ($this->Options['option'] as $icon) {
                        $link = \TAS\Core\Web::AppendQueryString($icon['link'], (isset($icon['paramname']) ? $icon['paramname'].'=' : 'id=').$row[$this->QueryOptions['indexfield']]);
                        $target = (isset($icon['target']) ? 'target="'.$icon['target'].'"' : '');
                        $option .= '<li><a class="'.$icon['tagname'].' btn btn-icons btn-rounded btn-outline-fa-color" '.$target.' data-toggle="tooltip" title="'.$icon['tooltip'].'"  href="'.$link.'"><i class="fas '.$icon['iconclass'].'"></i></a></li>';
                    }
                }

                $additionalClass = '';
                if (isset($this->Options['rowconditioncb']) && $this->Options['rowconditioncb'] != '' && function_exists($this->Options['rowconditioncb'])) {
                    $additionalClass = call_user_func($this->Options['rowconditioncb'], $row, $additionalClass);
                }
                $listing .= "\n".'<tr data-id="'.$row[$this->QueryOptions['indexfield']].'" id="row_'.$row[$this->QueryOptions['indexfield']].'"  class="griddatarow '.(($alt) ? 'gridrow' : 'altgridrow').' '.$additionalClass.'">';

                if ($ShowSelection) {
                    $listing .= '<td><input type="checkbox" name="select_'.$this->Options['tagname'].'['.$row[$this->QueryOptions['indexfield']].']" id="select_'.$this->Options['tagname'].'_'.$row[$this->QueryOptions['indexfield']].'" class="checkall_child"></td>';
                }

                $fieldCounter = 0;
                reset($this->Options['fields']);
                foreach ($this->Options['fields'] as $field => $val) {
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
                                $fielddata = '<a href="'.$corepage.'&id='.$row[$this->QueryOptions['indexfield']].'&type='.$field.'" class="'.$field.' gridinnerlink">'.$fielddata.'</a>';
                                $cssClass = 'gridtable-onoff';
                                break;
                            case 'flag':
                                $fielddata = (($row[$field] == 1 || strtolower($row[$field]) == 'active' || $row[$field] == true || strtolower($row[$field]) == 'yes') ? 'Yes' : 'No');
                                if ($fielddata == 'Yes') {
                                    $fielddata = '<img src="'.(isset($val['icon']) ? $val['icon'] : '{HomeURL}/theme/images/flag.png').'" class="gridimage flag '.$field.'">';
                                } else {
                                    $fielddata = '';
                                }
                                $fielddata = '<a href="'.$corepage.'&id='.$row[$this->QueryOptions['indexfield']].'&type='.$field.'" class="'.$field.' gridinnerlink">'.$fielddata.'</a>';
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
                                $format = (isset($val['DateFormat']) ?
                                            $val['DateFormat'] :
                                            (isset($GLOBALS['AppConfig']['DateFormat']) ? $GLOBALS['AppConfig']['DateFormat'] : 'm/d/Y'));

                                $fielddata = \TAS\Core\DataFormat::DBToDateFormat($row[$field], $format);
                                break;
                            case 'datetime':
                                $format = (isset($val['DateFormat']) ?
                                        $val['DateFormat'] :
                                    (isset($GLOBALS['AppConfig']['DateFormat']) ? $GLOBALS['AppConfig']['DateFormat'] : 'm/d/Y H:i a'));

                                $fielddata = \TAS\Core\DataFormat::DBToDateTimeFormat($row[$field], $format);
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

                    if (isset($val['link'])) {
                        $linkColumn = str_replace('{1}', $row[$val['linkfield']], $val['link']);
                        $listing .= '<td class="'.$cssClass.'"><a href="'.$linkColumn.'">'.$fielddata.'</a></td>';
                    } else {
                        $listing .= '<td class="'.$cssClass.'">'.$fielddata.'</td>';
                    }

                    ++$fieldCounter;
                }

                if (!$RemoveFieldOption) {
                    $listing .= '<td class="gridtable-optionrow"><ul class="table-ul">'.$option.'</ul></td></tr>';
                }
                $alt = !($alt);
            }

            $listing .= '</tbody>';
            if ($this->Options['allowpaging']) {
                $listing .= '<tfoot>'.$pageingrow.'</tfoot>';
            }
            $listing .= '</table></div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            </section>';
        } else {
            $listing = '<section class="content-section">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="content-area col-md-12 px-0">
                                <div class="col-lg-12 col-md-12 px-0">
                                  <div class="card">
                                    <div class="card-body"><h6> '.$this->Options['norecordtext'].' </h6>
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
}
