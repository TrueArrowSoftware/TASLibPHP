<?php

namespace TAS\Core;

class Grid
{
    public $QueryOptions;
    public $Options;

    private $CorePage;

    public function __construct($options = null, $queryoptions = null)
    {
        $this->QueryOptions = (null == $queryoptions ? Grid::DefaultQueryOptions() : $queryoptions);
        $this->Options = (null == $options ? Grid::DefaultOptions() : $options);
    }

    /**
     * Return the default options.
     */
    public static function DefaultOptions(): array
    {
        return [
            'gridurl' => '',
            'gridid' => 'mygrid',
            'tagname' => 'grid',
            'useget' => true, //Not used as of now, might be used in future to disable Values from GET.
            'pagesize' => $GLOBALS['AppConfig']['PageSize'] ?? 50,
            'allowsorting' => true,
            'allowpaging' => true,
            'showtotalrecord' => true,
            'totalrecordtext' => '{totalrecord} Records',
            'optionstext' => 'Options',
            'allowselection' => false,
            'roworder' => false,
            'fields' => [],
            'option' => [], //extraicons
            'rowconditioncallback' => [],
            'dateformat' => 'm/d/Y',
            'datetimeformat' => 'm/d/Y H:i:a',
            'norecordtext' => 'No record found',
            'currentpage' => 1,
            'showheaderfilter' => false,
            'filterdata' => [],
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
            'orderby' => [],
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
        $pagesize = $this->Options['pagesize'] ?? $GLOBALS['AppConfig']['PageSize'];

        $start = ($startpage - 1) * $pagesize;

        $this->QueryOptions['defaultorderby'] ??= '';
        $this->QueryOptions['defaultsortdirection'] ??= '';

        $orderby = ($_GET['ob'] ?? ($_SESSION[$this->Options['gridid'].$this->Options['tagname'].'_ob'] ?? $this->QueryOptions['defaultorderby']));
        $orderdirection = ((isset($_GET['d'])) ? $_GET['d'] : (($_SESSION[$this->Options['gridid'].$this->Options['tagname'].'_d'] ?? $this->QueryOptions['defaultsortdirection'])));

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
        if (isset($this->Options['noorderby']) && true == $this->Options['noorderby']) {
            $orderLine = ' ';
        } else {
            $orderLine = " order by {$orderby} {$orderdirection} {$sortstring} ";
        }

        if (isset($this->Options['allowpaging']) && false === $this->Options['allowpaging']) {
            $query = $this->QueryOptions['basicquery'].$this->QueryOptions['whereconditions'].$orderLine;
            if (isset($this->QueryOptions['recordshowlimit']) && is_numeric($this->QueryOptions['recordshowlimit']) && (int) $this->QueryOptions['recordshowlimit'] > 0) {
                $query .= ' limit '.(int) $this->QueryOptions['recordshowlimit'];
            }
        } else {
            $query = $this->QueryOptions['basicquery'].$this->QueryOptions['whereconditions'].$orderLine." limit {$start}, ".$pagesize;
        }

        $filter = '';

        $defaultpage = (!isset($this->Options['gridurl']) || '' == $this->Options['gridurl']) ? 'index.php' : $this->Options['gridurl'];
        $pagingPage = $defaultpage;
        $newdirection = ('asc' == strtolower($orderdirection)) ? 'desc' : 'asc';

        $page = \TAS\Core\Web::AppendQueryString($defaultpage, 'page='.$startpage.'&d='.$newdirection);
        $this->CorePage = \TAS\Core\Web::AppendQueryString($defaultpage, 'page='.$startpage);

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

        $TotalRecordCount = $GLOBALS['db']->ExecuteScalar('Select count(*) from ('.$this->QueryOptions['basicquery'].$this->QueryOptions['whereconditions'].') t');

        $recordText = $this->Options['totalrecordtext'] ?? '{totalrecord} records';
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

        $totalfield = 1;

        $allowRowSorting = ((isset($this->Options['roworder']) && true == $this->Options['roworder']) ? 'tablesort' : '');
        $listing .= '<div class="table-responsive">
                        <table class="table table-striped '.$allowRowSorting.'" data-url="'.$defaultpage.'" id="'.((isset($this->Options['tablename'])) ? $this->Options['tablename'] : 'usergrid').'">';
        $listing .= '<thead>
            <tr>';
        if ($this->Options['allowselection']) {
            $listing .= '<th style="width: 20px"><input type="checkbox" name="select_'.$this->Options['tagname'].'" id="select_'.$this->Options['tagname'].'" class="checkall"></th>';
            ++$totalfield;
        }

        $RemoveFieldOption = false;
        if (isset($this->Options['option']) && is_array($this->Options['option']) && 0 == count($this->Options['option'])) {
            $RemoveFieldOption = true;
            --$totalfield;
        }

        reset($this->Options['fields']);

        foreach ($this->Options['fields'] as $field => $val) {
            $sorticon = '';
            if ($orderby == $field || (isset($val['sortstring']) && $orderby == $val['sortstring'])) {
                if ('asc' == strtolower($orderdirection)) {
                    $sorticon = '<a class="ui-state-default ui-icon-gap ui-corner-all" href="'.$page.'&ob='.$field.'">
                    <i class="fas fa-sort-alpha-up"></i></a>';
                } else {
                    $sorticon = '<a  class="ui-state-default ui-icon-gap ui-corner-all" href="'.$page.'&ob='.$field.'">
                    <i class="fas fa-sort-alpha-down"></i></a>';
                }
            }
            $Text = (isset($val['icon']) ? $val['icon'].' ' : '').$val['name'];
            $Label = $val['label'] ?? $val['name'];

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
            if (isset($this->Options['allowsorting']) && false === $this->Options['allowsorting']) {
                $listing .= $Text;
            } else {
                $listing .= '<a href="'.$page.'&ob='.$field.'" title="Sort by '.$Label.'">'.$Text.'</a>'.$sorticon.'</th>';
            }
            ++$totalfield;
        }

        if (!$RemoveFieldOption) {
            $listing .= '	<th><a href="#">'.$this->Options['optionstext'].'</a></th>';
        }
        $listing .= '</tr>';

        if ($this->Options['showheaderfilter']) {
            $listing .= $this->ShowFilter();
        }

        if ($this->Options['allowpaging']) {
            $pquery = $this->QueryOptions['pagingquery'] ?? $this->QueryOptions['basicquery'];
            $pageingrow = '<tr><td class="pager" colspan="'.$totalfield.'">'.
                \TAS\Core\Utility::Paging(
                    $this->QueryOptions['tablename'],
                    $pagingPage,
                    $startpage,
                    $pquery.$this->QueryOptions['whereconditions'].($this->QueryOptions['pagingqueryend'] ?? ''),
                    $filter,
                    true,
                    [
                        'pagesize' => $pagesize,
                    ]
                ).'</td></tr>';

            $listing .= $pageingrow;
        }

        $listing .= '</thead><tbody>';

        if ($GLOBALS['db']->RowCount($rs) > 0) {
            $alt = true;
            $RowValueTotal = [];
            foreach ($rs as $row) {
                $option = '';
                if (isset($this->Options['option']) && is_array($this->Options['option'])) {
                    foreach ($this->Options['option'] as $icon) {
                        $link = \TAS\Core\Web::AppendQueryString($icon['link'], (isset($icon['paramname']) ? $icon['paramname'].'=' : 'id=').$row[$this->QueryOptions['indexfield']]);
                        $target = (isset($icon['target']) ? 'target="'.$icon['target'].'"' : '');
                        $option .= '<li><a class="'.$icon['tagname'].' btn btn-icons btn-rounded btn-outline-fa-color" '.$target.' data-toggle="tooltip" title="'.$icon['tooltip'].'"  href="'.$link.'"><i class="fas '.$icon['iconclass'].'"></i></a></li>';
                    }
                }

                $additionalClass = '';
                if (isset($this->Options['rowconditioncallback'])
                    && ((is_array($this->Options['rowconditioncallback']) && count($this->Options['rowconditioncallback']) > 0)
                       || (is_string($this->Options['rowconditioncallback']) && strlen($this->Options['rowconditioncallback']) > 0))
                    ) {
                    $additionalClass = call_user_func($this->Options['rowconditioncallback'], $row, $additionalClass);
                }
                $listing .= "\n".'<tr data-id="'.$row[$this->QueryOptions['indexfield']].'" id="row_'.$row[$this->QueryOptions['indexfield']].'"  class="griddatarow '.(($alt) ? 'gridrow' : 'altgridrow').' '.$additionalClass.'">';

                if ($this->Options['allowselection']) {
                    $listing .= '<td><input type="checkbox" name="select_'.$this->Options['tagname'].'['.$row[$this->QueryOptions['indexfield']].']" id="select_'.$this->Options['tagname'].'_'.$row[$this->QueryOptions['indexfield']].'" class="checkall_child"></td>';
                }

                $fieldCounter = 0;

                reset($this->Options['fields']);
                foreach ($this->Options['fields'] as $field => $val) {
                    $fielddata = '';
                    $cssClass = '';

                    $_output = $this->ProcessColumnData($field, $val, $row);
                    $fielddata = $_output['fielddata'];
                    $cssClass = $_output['cssClass'];

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
            $listing .= '<tfoot>';
            if (count($RowValueTotal) > 0) {
                $listing .= '<tr class="rowvaluetotal">';
                foreach ($this->Options['fields'] as $field => $val) {
                    if (isset($RowValueTotal[$field])) {
                        switch ($val['type']) {
                            case 'numeric':
                            case 'number':
                                if (isset($val['number-decimal']) && $val['number-decimal']) {
                                    $_v = number_format((float) $RowValueTotal[$field], 2);
                                } else {
                                    $_v = (int) round((float) $RowValueTotal[$field], 4);
                                }
                                $listing .= '<td>'.$_v.'</td>';

                                break;

                            case 'currency':
                                if (isset($val['postsymbol']) && $val['postsymbol']) {
                                    $_v = number_format((float) $RowValueTotal[$field], 2).$val['postsymbol'];
                                } else {
                                    $_v = $GLOBALS['AppConfig']['Currency'].number_format((float) $RowValueTotal[$field], 2);
                                }

                                $listing .= '<td>'.$_v.'</td>';

                                break;

                            case 'callback':
                            case 'cb':
                                $listing .= '<td>'.(int) round((float) $RowValueTotal[$field], 4).'</td>';

                                break;
                        }
                    } else {
                        $listing .= '<td></td>';
                    }
                }
                $listing .= '</tr>';
            }
            if ($this->Options['allowpaging']) {
                $listing .= $pageingrow;
            }
            $listing .= '</tfoot></table></div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            </section>';
        } else {
            if ($this->Options['showheaderfilter']) {
                $listing .= '<tbody><tr><td colspan="'.$totalfield.'"><h6> '.$this->Options['norecordtext'].'</td></tr></tbody></table></div>
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
        }

        return $listing;
    }

    public function ProcessColumnData($field, $val, $row)
    {
        $fielddata = '';
        $cssClass = '';

        switch ($val['type']) {
            case 'globalarray':
                if (null != $row[$field]) {
                    if (isset($val['arrayname'])) {
                        $fielddata = $GLOBALS[$val['arrayname']][$row[$field]];
                    } else {
                        $fielddata = $GLOBALS[$field][$row[$field]];
                    }
                }

                break;

            case 'string':
                $fielddata = $row[$field];

                break;

            case 'longstring':
                $fielddata = $row[$field];

                break;

            case 'onoff':
                $fielddata = ((1 === (int) $row[$field] || 'active' === strtolower($row[$field]) || true === $row[$field] || 'yes' === strtolower($row[$field])) ? 'Yes' : 'No');

                if (isset($val['mode']) && 'fa' == $val['mode']) {
                    if ('Yes' == $fielddata) {
                        $fielddata = ' <i class="fas '.($val['iconyes'] ?? 'fa-heart').' green" aria-hidden="true"></i>';
                    } else {
                        $fielddata = ' <i class="fas '.($val['iconno'] ?? 'fa-heart').' red" aria-hidden="true"></i>';
                    }
                } else {
                    if ('Yes' == $fielddata && isset($val['iconyes'])) {
                        $fielddata = '<img src="'.$val['iconyes'].'" class="gridimage '.$field.'">';
                    }
                    if ('No' == $fielddata && isset($val['iconno'])) {
                        $fielddata = '<img src="'.$val['iconno'].'" class="gridimage '.$field.'">';
                    }
                }
                $fielddata = '<a href="'.$this->CorePage.'&id='.$row[$this->QueryOptions['indexfield']].'&type='.$field.'" class="'.$field.' gridinnerlink">'.$fielddata.'</a>';
                $cssClass = 'gridtable-onoff';

                break;

            case 'flag':
                $fielddata = ((1 == $row[$field] || 'active' == strtolower($row[$field]) || true == $row[$field] || 'yes' == strtolower($row[$field])) ? 'Yes' : 'No');
                if ('Yes' == $fielddata) {
                    $fielddata = '<img src="'.($val['icon'] ?? '{HomeURL}/theme/images/flag.png').'" class="gridimage flag '.$field.'">';
                } else {
                    $fielddata = '';
                }
                $fielddata = '<a href="'.$this->CorePage.'&id='.$row[$this->QueryOptions['indexfield']].'&type='.$field.'" class="'.$field.' gridinnerlink">'.$fielddata.'</a>';
                $cssClass = 'gridtable-flag';

                break;

            case 'phone':
                if ('' != $row[$field]) {
                    $fielddata = \TAS\Core\DataFormat::FormatPhone($row[$field], $val['PhoneLength'] ?? 10);
                } else {
                    $fielddata = $row[$field];
                }

                break;

            case 'date':
                $format = ($val['DateFormat'] ?? (Config::$DisplayDateFormat ?? 'm/d/Y'));
                $fielddata = \TAS\Core\DataFormat::DBToDateFormat($row[$field], $format);

                break;

            case 'datetime':
                $format = ($val['DateFormat'] ?? (Config::$DisplayDateTimeFormat ?? 'm/d/Y H:i a'));
                $fielddata = \TAS\Core\DataFormat::DBToDateTimeFormat($row[$field], $format);

                break;

            case 'currency':
                $cssClass = 'gridtable-currency';
                if ($val['showtotal'] ?? false == true) {
                    $RowValueTotal[$field] ??= 0.0;
                    $RowValueTotal[$field] += (float) $row[$field];
                }
                if (isset($val['postsymbol']) && $val['postsymbol']) {
                    $fielddata = number_format((float) $row[$field], 2).$val['postsymbol'];
                } else {
                    $fielddata = $GLOBALS['AppConfig']['Currency'].number_format(floatval($row[$field]), 2);
                }

                break;

            case 'numeric':
            case 'number':
                $cssClass = 'gridtable-numeric';
                if (($val['showtotal'] ?? false) == true) {
                    $RowValueTotal[$field] ??= 0.0;
                    $RowValueTotal[$field] += (float) $row[$field];
                }

                if (isset($val['number-decimal']) && $val['number-decimal']) {
                    $fielddata = number_format((float) $row[$field], 2);
                } else {
                    $fielddata = (int) round((float) $row[$field], 4);
                }

                break;

            case 'cb':
            case 'callback':
                if (($val['showtotal'] ?? false) == true) {
                    $RowValueTotal[$field] ??= 0.0;
                    $t = $RowValueTotal[$field];
                    $fielddata = call_user_func_array($val['function'], [$row, $field, &$t]);
                    $RowValueTotal[$field] = $t;
                } else {
                    $fielddata = call_user_func_array($val['function'], [$row, $field]);
                }

                break;

            case 'image':
                $fielddata = '<img src="'.($val['prefixUrl'] ?? '').$row[$field].'" class="thumbnailsize">';

                break;

            case 'color':
                $fielddata = '<div class="colordiv" style="background:'.$row[$field].';"></div>';

                break;

            default:
                $fielddata = $row[$field];

                break;
        }

        return ['fielddata' => $fielddata, 'cssClass' => $cssClass];
    }

    private function ShowFilter()
    {
        $listing = '<tr>';
        if ($this->Options['allowselection']) {
            $listing .= '<th style="width: 20px">&nbsp;</th>';
        }

        $RemoveFieldOption = false;
        if (isset($this->Options['option']) && is_array($this->Options['option']) && 0 == count($this->Options['option'])) {
            $RemoveFieldOption = true;
        }

        reset($this->Options['fields']);

        foreach ($this->Options['fields'] as $field => $val) {
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
            $v = isset($this->Options['filterdata']) ? (DataFormat::DoSecure($this->Options['filterdata'][$this->Options['gridid'].'-filter-'.$field] ?? '')) : '';
            $listing .= '<input type="text" class="filter-textbox" id="'.$this->Options['gridid'].'-filter-'.$field.'" name="'.$this->Options['gridid'].'-filter-'.$field.'" value="'.$v.'"></th>';
        }

        if (!$RemoveFieldOption) {
            $listing .= '	<th>&nbsp;</th>';
        }
        $listing .= '</tr>';

        return $listing;
    }
}
