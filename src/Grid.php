<?php

namespace TAS\Core;

use TAS\Core\Async\AsyncQuery;
use TAS\Core\Async\DBPool;
use TAS\Core\GridFilter\DateFilter;
use TAS\Core\GridFilter\IGridFilter;
use TAS\Core\GridFilter\SelectFilter;
use TAS\Core\GridFilter\TextFilter;
use TAS\Core\UI\GridBootstrap;
use TAS\Core\UI\IGridUI;

class Grid
{
    // Seek mode only: a page number from the URL is capped so skip arithmetic can't overflow into a float.
    private const MAX_PAGE = 1000000000;

    public $QueryOptions;
    public $Options;
    public static IGridUI $UI;

    private $CorePage;

    /**
     * @param array|null $options
     * @param array|null $queryoptions
     */
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
            'useget' => true, // Not used as of now, might be used in future to disable Values from GET.
            'pagesize' => $GLOBALS['AppConfig']['PageSize'] ?? 50,
            'allowsorting' => true,
            'allowpaging' => true,
            'showtotalrecord' => true,
            'totalrecordtext' => '{totalrecord} Records',
            'optionstext' => 'Options',
            'allowselection' => false,
            'roworder' => false,
            'fields' => [],
            'option' => [], // extraicons
            'rowconditioncallback' => [],
            'dateformat' => 'm/d/Y',
            'datetimeformat' => 'm/d/Y H:i:a',
            'norecordtext' => 'No record found',
            'currentpage' => 1,
            'showheaderfilter' => false,
            'filterdata' => [],
            // 'offset' = limit x,y paging. 'seek' = anchor row + short skip (see KeysetPager), fast on deep pages of big tables.
            // Seek needs queryoptions 'indexfield' and a 'seek' key on every sortable field, else that sort pages by offset.
            'pagingmode' => 'offset',
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
            'indexexpr' => '', // seek mode only: SQL expression of indexfield when the name alone is ambiguous in a join.
            'orderby' => [],
            'noorderby' => false, // in case your query has it.
            'recordshowlimit' => 0,
            'tablename' => '',
        ];
    }

    /**
     * @return array
     */
    public function DefaultIcon(): array
    {
        return [
            'edit' => [
                'link' => $this->Options['gridurl'],
                'iconclass' => 'fa-edit',
                'tooltip' => 'Edit this record',
                'tagname' => 'edit',
                'paramname' => 'id',
            ],
            'delete' => [
                'link' => $this->Options['gridurl'],
                'iconclass' => 'fa-trash',
                'tooltip' => 'Delete this record',
                'tagname' => 'delete btn-outline-danger',
                'paramname' => 'delete',
            ],
        ];
    }

    /**
     * @return string
     */
    public function Render(): string
    {
        $listing = '';
        $startpage = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int) $_GET['page'] : (int) $this->Options['currentpage']);
        $pagesize = $this->Options['pagesize'] ?? $GLOBALS['AppConfig']['PageSize'];

        $start = ($startpage - 1) * $pagesize;

        $this->QueryOptions['defaultorderby'] ??= '';
        $this->QueryOptions['defaultsortdirection'] ??= '';

        // ob and d go straight into the order by, so only accept a known column and asc/desc,
        // from the URL and from the session (which an older version may have filled with anything).
        $sessionKey = $this->Options['gridid'] . $this->Options['tagname'];
        $sessionOrder = $_SESSION[$sessionKey . '_ob'] ?? null;
        $sessionDirection = $_SESSION[$sessionKey . '_d'] ?? null;

        $orderby = $this->IsSortColumn($_GET['ob'] ?? null) ? $_GET['ob']
            : ($this->IsSortColumn($sessionOrder) ? $sessionOrder : $this->QueryOptions['defaultorderby']);
        $orderdirection = self::IsDirection($_GET['d'] ?? null) ? $_GET['d']
            : (self::IsDirection($sessionDirection) ? $sessionDirection : $this->QueryOptions['defaultsortdirection']);

        $_SESSION[$this->Options['gridid'] . $this->Options['tagname'] . '_d'] = $orderdirection;
        $_SESSION[$this->Options['gridid'] . $this->Options['tagname'] . '_ob'] = $orderby;

        $sortstring = '';
        if (isset($this->QueryOptions['orderby']) && is_array($this->QueryOptions['orderby'])) {
            foreach ($this->QueryOptions['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (trim($tmpsplit[0]) != $orderby) {
                    $sortstring .= ', ' . $val;
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

        $TotalRecordCount = null;
        $seek = null;
        $plan = null;
        $cursor = null;
        $hash = '';
        $seekRequested = (!isset($this->Options['allowpaging']) || false !== $this->Options['allowpaging']) && 'seek' === ($this->Options['pagingmode'] ?? 'offset');
        $seekWhyNot = null;
        if ($seekRequested) {
            $seek = $this->BuildSeek((string) $orderby, (string) $orderdirection, $seekWhyNot);
        }

        if (isset($this->Options['allowpaging']) && false === $this->Options['allowpaging']) {
            $query = $this->QueryOptions['basicquery'] . $this->QueryOptions['whereconditions'] . $orderLine;
            if (isset($this->QueryOptions['recordshowlimit']) && is_numeric($this->QueryOptions['recordshowlimit']) && (int) $this->QueryOptions['recordshowlimit'] > 0) {
                $query .= ' limit ' . (int) $this->QueryOptions['recordshowlimit'];
            }
        } elseif (null !== $seek) {
            $pagesize = max(1, (int) $pagesize);
            $startpage = min(max(1, $startpage), self::MAX_PAGE);
            $hash = $seek->Hash($this->QueryOptions['whereconditions'] . '|' . $pagesize);
            $cursor = $seek->Decode(is_string($_GET['cur'] ?? null) ? $_GET['cur'] : '', $hash);

            if (!empty($_GET['last'])) {
                // Last page is read backwards from the end, which needs the total first.
                $TotalRecordCount = (int) $GLOBALS['db']->ExecuteScalar($this->SeekCountQuery());
                $startpage = max(1, (int) ceil($TotalRecordCount / $pagesize));
            }
            $plan = $seek->Plan($startpage, $pagesize, $cursor, $TotalRecordCount);

            $condition = ('after' === $plan['mode'] || 'before' === $plan['mode'])
                ? $seek->Seek($plan['values'], 'after' === $plan['mode'], $plan['inclusive'], [$GLOBALS['db'], 'Escape'])
                : '';
            $reverse = ('before' === $plan['mode'] || 'last' === $plan['mode']);
            $query = $this->QueryOptions['basicquery'] . KeysetPager::Where($this->QueryOptions['whereconditions'], $condition)
                . ' order by ' . $seek->OrderBy($reverse) . " limit {$plan['skip']}, {$plan['limit']}";
        } else {
            $query = $this->QueryOptions['basicquery'] . $this->QueryOptions['whereconditions'] . $orderLine . " limit {$start}, " . $pagesize;
        }

        $filter = '';

        $defaultpage = (!isset($this->Options['gridurl']) || '' == $this->Options['gridurl']) ? 'index.php' : $this->Options['gridurl'];
        $pagingPage = $defaultpage;
        $newdirection = ('asc' == strtolower($orderdirection)) ? 'desc' : 'asc';

        // A new sort starts at page 1 in seek mode, the same page number would need a deep offset.
        $page = Web::AppendQueryString($defaultpage, 'page=' . (null !== $seek ? 1 : $startpage) . '&d=' . $newdirection);
        $this->CorePage = Web::AppendQueryString($defaultpage, 'page=' . $startpage);

        if (isset($_GET['direction'])) {
            $filter .= '&direction=' . $orderdirection;
        }
        if (isset($_GET['orderby'])) {
            $filter .= '&orderby=' . $orderby;
        }
        // Seek mode counts once, with the query the pager always used. Offset mode keeps counting twice.
        $countQuery = (null !== $seek)
            ? $this->SeekCountQuery()
            : 'Select count(*) from (' . $this->QueryOptions['basicquery'] . $this->QueryOptions['whereconditions'] . ') t';

        // Seek mode prints before running, so a query that fails or throws is still visible.
        if ($seekRequested && $GLOBALS['AppConfig']['DebugMode']) {
            echo $this->SeekDebugText($query, $countQuery, $plan, $cursor, $seekWhyNot);
        }

        // Run data query and count query in parallel when a connection pool is available
        if (isset($GLOBALS['dbpool']) && $GLOBALS['dbpool'] instanceof DBPool) {
            $parallelQueries = ['data' => $query];
            if (null === $TotalRecordCount) {
                $parallelQueries['count'] = $countQuery;
            }

            // Dropdown filter source queries join the same parallel batch.
            $selectFilters = [];
            if ($this->Options['showheaderfilter']) {
                foreach ($this->Options['fields'] as $field => $val) {
                    $f = $val['filter'] ?? null;
                    if ($f instanceof SelectFilter && is_string($f->source) && null === $f->rs) {
                        $selectFilters['filter_' . $field] = $f;
                        $parallelQueries['filter_' . $field] = $f->source;
                    }
                }
            }

            $parallelResults = AsyncQuery::runParallel($parallelQueries, $GLOBALS['dbpool']);
            foreach ($selectFilters as $key => $f) {
                $f->rs = $parallelResults[$key];
            }
            $rs = $parallelResults['data'];
            if (null === $TotalRecordCount) {
                $countResult = $parallelResults['count'];
                $TotalRecordCount = ($countResult instanceof \mysqli_result && $countResult->num_rows > 0)
                    ? $countResult->fetch_row()[0] : 0;
            }
        } else {
            $rs = $GLOBALS['db']->Execute($query);
            $TotalRecordCount ??= $GLOBALS['db']->ExecuteScalar($countQuery);
        }

        // Seek mode reads backwards for before/last, so the page is put back in display order here.
        $rows = $rs;
        $cursorToken = null;
        if (null !== $seek) {
            $rows = ($rs instanceof \Traversable) ? iterator_to_array($rs, false) : [];
            if ('before' === $plan['mode'] || 'last' === $plan['mode']) {
                $rows = array_reverse($rows);
            }
            if (count($rows) > 0) {
                $cursorToken = $seek->Encode($startpage, $rows[0], $rows[count($rows) - 1], $hash);
            }
        }

        if ($GLOBALS['AppConfig']['DebugMode'] && !$seekRequested) {
            echo $query;
        }

        $recordText = $this->Options['totalrecordtext'] ?? '{totalrecord} records';
        $recordText = str_replace('{totalrecord}', $TotalRecordCount, $recordText);

        $listing .= self::$UI->GetBeforeTable();

        if ($this->Options['showtotalrecord']) {
            $listing .= '<h6>' . $recordText . ' </h6>';
        }

        $totalfield = 1;

        $allowRowSorting = ((isset($this->Options['roworder']) && true == $this->Options['roworder']) ? 'tablesort' : '');
        $_tableID = ((isset($this->Options['tablename'])) ? $this->Options['tablename'] : 'usergrid');

        $listing .= self::$UI->GetTableStart($_tableID, $allowRowSorting, $defaultpage);
        $listing .= self::$UI->GetAtTableHeadStart();

        if ($this->Options['allowselection']) {
            $listing .= '<th style="width: 20px">
                <input type="checkbox" name="select_' . $this->Options['tagname'] . '" id="select_' . $this->Options['tagname'] . '" class="checkall"></th>';
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
                $sorticon = '<a class="' . self::$UI->GetCssClassForSortingIcon() . '" href="' . $page . '&ob=' . $field . '">
                    <i class="fas ' . (('asc' == strtolower($orderdirection)) ? 'fa-sort-alpha-up' : 'fa-sort-alpha-down') . '"></i></a>';
            }

            $Text = (isset($val['icon']) ? $val['icon'] . ' ' : '') . $val['name'];
            $Label = $val['label'] ?? $val['name'];

            switch ($val['type']) {
                case 'longstring':
                    $count = count($this->Options['fields']);
                    ++$count;
                    $count = (int) ((2 / $count) * 100);
                    $listing .= '<th style="width:' . $count . '%;">';

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
                $listing .= '<a href="' . $page . '&ob=' . $field . '" title="Sort by ' . $Label . '">' . $Text . '</a>' . $sorticon . '</th>';
            }
            ++$totalfield;
        }

        if (!$RemoveFieldOption) {
            $listing .= '	<th><a href="#">' . $this->Options['optionstext'] . '</a></th>';
        }
        $listing .= '</tr>';

        if ($this->Options['showheaderfilter']) {
            $listing .= $this->ShowFilter();
        }

        if ($this->Options['allowpaging']) {
            if (null !== $seek) {
                $pager = KeysetPager::Links($pagingPage, $startpage, (int) $TotalRecordCount, $pagesize, $cursorToken, $filter);
            } else {
                $pquery = $this->QueryOptions['pagingquery'] ?? $this->QueryOptions['basicquery'];
                $pager = Utility::Paging(
                    $this->QueryOptions['tablename'],
                    $pagingPage,
                    $startpage,
                    $pquery . $this->QueryOptions['whereconditions'] . ($this->QueryOptions['pagingqueryend'] ?? ''),
                    $filter,
                    true,
                    ['pagesize' => $pagesize]
                );
            }
            $pageingrow = '<tr class="pagetr"><td colspan="' . $totalfield . '"><div class="pager">' . $pager . '</div></td></tr>';

            $listing .= $pageingrow;
        }

        $listing .= self::$UI->GetAtTableHeader();
        $listing .= self::$UI->GetAtTableHeadEnd();
        $listing .= '<tbody>';

        $listing .= self::$UI->GetAtTableBody();

        if ((null !== $seek) ? count($rows) > 0 : $GLOBALS['db']->RowCount($rs) > 0) {
            $alt = true;
            $RowValueTotal = [];

            $optionTemplate = '';
            if (isset($this->Options['option']) && is_array($this->Options['option'])) {
                foreach ($this->Options['option'] as $icon) {
                    $link = Web::AppendQueryString($icon['link'], (isset($icon['paramname']) ? $icon['paramname'] . '=' : 'id=') . 'ROW_ID_TPL');
                    $link = str_replace('ROW_ID_TPL', '{_row_id_}', $link);
                    $target = (isset($icon['target']) ? 'target="' . $icon['target'] . '"' : '');
                    $optionTemplate .= '<li><a class="' . $icon['tagname'] . ' btn btn-icons btn-rounded btn-outline-fa-color" ' . $target . ' data-toggle="tooltip" title="' . $icon['tooltip'] . '"  href="' . $link . '"><i class="fas ' . $icon['iconclass'] . '"></i></a></li>';
                }
            }

            $hasRowConditionCallback = false;
            if (
                isset($this->Options['rowconditioncallback'])
                && ((is_array($this->Options['rowconditioncallback']) && count($this->Options['rowconditioncallback']) > 0)
                    || (is_string($this->Options['rowconditioncallback']) && strlen($this->Options['rowconditioncallback']) > 0))
            ) {
                $hasRowConditionCallback = true;
            }

            $allowSelection = $this->Options['allowselection'];
            $tagName = $this->Options['tagname'];
            $indexField = $this->QueryOptions['indexfield'];

            $fieldsCache = [];
            foreach ($this->Options['fields'] as $field => $val) {
                $fieldsCache[$field] = [
                    'val' => $val,
                    'hasLink' => isset($val['link']),
                    'link' => $val['link'] ?? '',
                    'linkfield' => $val['linkfield'] ?? '',
                ];
            }

            $rowsHtml = [];
            foreach ($rows as $row) {
                $rowHtml = '';
                $rowId = $row[$indexField] ?? '';
                $option = '';
                if ($optionTemplate !== '') {
                    $option = str_replace('{_row_id_}', $rowId, $optionTemplate);
                }

                $additionalClass = '';
                if ($hasRowConditionCallback) {
                    $additionalClass = call_user_func($this->Options['rowconditioncallback'], $row, $additionalClass);
                }
                $rowHtml .= "\n" . '<tr data-id="' . $rowId . '" id="row_' . $rowId . '"  class="griddatarow ' . (($alt) ? 'gridrow' : 'altgridrow') . ' ' . $additionalClass . '">';

                if ($allowSelection) {
                    $rowHtml .= '<td><input type="checkbox" name="select_' . $tagName . '[' . $rowId . ']" id="select_' . $tagName . '_' . $rowId . '" class="checkall_child"></td>';
                }

                foreach ($fieldsCache as $field => $fc) {
                    $_output = $this->ProcessColumnData($field, $fc['val'], $row, $RowValueTotal);
                    $fielddata = $_output['fielddata'] ?? '';
                    $cssClass = $_output['cssClass'] ?? '';

                    $rowHtml .= "\r\n \t";

                    if ($fc['hasLink']) {
                        $linkColumn = str_replace('{1}', $row[$fc['linkfield']], $fc['link']);
                        $rowHtml .= '<td class="' . $cssClass . '"><a href="' . $linkColumn . '">' . $fielddata . '</a></td>';
                    } else {
                        $rowHtml .= '<td class="' . $cssClass . '">' . $fielddata . '</td>';
                    }
                }

                if (!$RemoveFieldOption) {
                    $rowHtml .= '<td class="gridtable-optionrow"><ul class="table-ul">' . $option . '</ul></td></tr>';
                }

                $rowsHtml[] = $rowHtml;
                $alt = !$alt;
            }
            $listing .= implode('', $rowsHtml);

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
                                $listing .= '<td>' . $_v . '</td>';

                                break;

                            case 'currency':
                                if (isset($val['postsymbol']) && $val['postsymbol']) {
                                    $_v = number_format((float) $RowValueTotal[$field], 2) . $val['postsymbol'];
                                } else {
                                    $_v = $GLOBALS['AppConfig']['Currency'] . number_format((float) $RowValueTotal[$field], 2);
                                }

                                $listing .= '<td>' . $_v . '</td>';

                                break;

                            case 'callback':
                            case 'cb':
                                $listing .= '<td>' . (int) round((float) $RowValueTotal[$field], 4) . '</td>';

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
            $listing .= self::$UI->GetAtTableFooter();
            $listing .= '</tfoot>';
            $listing .= self::$UI->GetTableEnd();
            $listing .= self::$UI->GetAfterTable();
        } else {
            if ($this->Options['showheaderfilter']) {
                $listing .= '<tbody><tr><td colspan="' . $totalfield . '"><h6> ' . $this->Options['norecordtext'] . '</td></tr></tbody>' .
                    self::$UI->GetTableEnd() .
                    self::$UI->GetAfterTable();
            } else {
                $listing = self::$UI->GetNoRecordFound($this->Options['norecordtext'] ?? 'No Record Found');
            }
        }

        return $listing;
    }

    /**
     * @param string $field
     * @param array $val
     * @param array $row
     * @param array $RowValueTotal
     * @return array
     */
    public function ProcessColumnData($field, $val, $row, &$RowValueTotal)
    {
        $fielddata = '';
        $cssClass = '';

        switch ($val['type']) {
            case 'globalarray':
                if (array_key_exists($field, $row) && null != $row[$field]) {
                    if (isset($val['arrayname'])) {
                        $fielddata = $GLOBALS[$val['arrayname']][$row[$field]] ?? "";
                    } else {
                        $fielddata = $GLOBALS[$field][$row[$field]] ?? "";
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
                $_fieldvalue = strtolower($row[$field] ?? '0');
                $fielddata = ((1 === (int) $_fieldvalue || 'active' === $_fieldvalue || true === $_fieldvalue || 'yes' === $_fieldvalue) ? 'Yes' : 'No');

                if (isset($val['mode']) && 'fa' == $val['mode']) {
                    if ('Yes' == $fielddata) {
                        $fielddata = ' <i class="fas ' . ($val['iconyes'] ?? 'fa-heart') . ' green" aria-hidden="true"></i>';
                    } else {
                        $fielddata = ' <i class="fas ' . ($val['iconno'] ?? 'fa-heart') . ' red" aria-hidden="true"></i>';
                    }
                } else {
                    if ('Yes' == $fielddata && isset($val['iconyes'])) {
                        $fielddata = '<img src="' . $val['iconyes'] . '" class="gridimage ' . $field . '">';
                    }
                    if ('No' == $fielddata && isset($val['iconno'])) {
                        $fielddata = '<img src="' . $val['iconno'] . '" class="gridimage ' . $field . '">';
                    }
                }
                $fielddata = '<a href="' . $this->CorePage . '&id=' . $row[$this->QueryOptions['indexfield']] . '&type=' . $field . '" class="' . $field . ' gridinnerlink">' . $fielddata . '</a>';
                $cssClass = 'gridtable-onoff';

                break;

            case 'flag':
                $fielddata = ((1 == $row[$field] || 'active' == strtolower($row[$field]) || true == $row[$field] || 'yes' == strtolower($row[$field])) ? 'Yes' : 'No');
                if ('Yes' == $fielddata) {
                    $fielddata = '<img src="' . ($val['icon'] ?? '{HomeURL}/theme/images/flag.png') . '" class="gridimage flag ' . $field . '">';
                } else {
                    $fielddata = '';
                }
                $fielddata = '<a href="' . $this->CorePage . '&id=' . $row[$this->QueryOptions['indexfield']] . '&type=' . $field . '" class="' . $field . ' gridinnerlink">' . $fielddata . '</a>';
                $cssClass = 'gridtable-flag';

                break;

            case 'phone':
                if ('' != $row[$field]) {
                    $fielddata = DataFormat::FormatPhone($row[$field], $val['PhoneLength'] ?? 10);
                } else {
                    $fielddata = $row[$field];
                }

                break;

            case 'date':
                $format = ($val['DateFormat'] ?? (Config::$DisplayDateFormat ?? 'm/d/Y'));
                $fielddata = DataFormat::DBToDateFormat($row[$field], $format);

                break;

            case 'datetime':
                $format = ($val['DateFormat'] ?? (Config::$DisplayDateTimeFormat ?? 'm/d/Y H:i a'));
                $fielddata = DataFormat::DBToDateTimeFormat($row[$field], $format);

                break;

            case 'currency':
                $cssClass = 'gridtable-currency';
                if ($val['showtotal'] ?? false == true) {
                    $RowValueTotal[$field] ??= 0.0;
                    $RowValueTotal[$field] += (float) $row[$field];
                }
                if (isset($val['postsymbol']) && $val['postsymbol']) {
                    $fielddata = number_format((float) $row[$field], 2) . $val['postsymbol'];
                } else {
                    $fielddata = $GLOBALS['AppConfig']['Currency'] . number_format(floatval($row[$field]), 2);
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
                $fielddata = '<img src="' . ($val['prefixUrl'] ?? '') . $row[$field] . '" class="thumbnailsize">';

                break;

            case 'color':
                $fielddata = '<div class="colordiv" style="background:' . $row[$field] . ';"></div>';

                break;

            default:
                $fielddata = $row[$field];

                break;
        }

        return ['fielddata' => $fielddata, 'cssClass' => $cssClass];
    }

    private static function IsDirection($d): bool
    {
        return is_string($d) && in_array(strtolower($d), ['asc', 'desc'], true);
    }

    /**
     * ob is only a sort column when the grid itself offers it, anything else would be SQL from the URL.
     */
    private function IsSortColumn($ob): bool
    {
        if (!is_string($ob) || '' === $ob) {
            return false;
        }
        if ($ob === ($this->QueryOptions['defaultorderby'] ?? '')) {
            return true;
        }
        foreach ($this->Options['fields'] as $field => $val) {
            if ((string) $field === $ob || (is_array($val) && ($val['sortstring'] ?? null) === $ob)) {
                return true;
            }
        }
        foreach ((array) ($this->QueryOptions['orderby'] ?? []) as $extra) {
            if (preg_split('/\s+/', trim((string) $extra))[0] === $ob) {
                return true;
            }
        }

        return false;
    }

    /**
     * Seek paging is only used for a plain "basicquery + where" shape, anything else pages by offset.
     */
    private function BuildSeek(string $orderby, string $direction, ?string &$whyNot = null): ?KeysetPager
    {
        if (!empty($this->Options['noorderby']) || !empty($this->QueryOptions['noorderby'])) {
            $whyNot = "'noorderby' is set";

            return null;
        }

        $where = trim($this->QueryOptions['whereconditions'] ?? '');
        if ('' === $where) {
            // A where inside basicquery can't be told from a subquery's, so we don't append to it.
            if (preg_match('/\bwhere\b/i', $this->QueryOptions['basicquery'])) {
                $whyNot = "'whereconditions' is blank but 'basicquery' has its own where, move it into 'whereconditions'";

                return null;
            }
        } elseif (0 !== stripos($where, 'where') || preg_match('/\b(group\s+by|having|order\s+by|limit|union)\b|;|--|#|\/\*/i', $where)) {
            // Anything that could end or comment out the condition we append (or the parenthesis around the where) is not safe to extend.
            $whyNot = "'whereconditions' is not a plain \"where ...\" clause (group by/having/order by/limit/union/;/--/#//* are not allowed)";

            return null;
        }

        $extra = is_array($this->QueryOptions['orderby'] ?? null) ? $this->QueryOptions['orderby'] : [];
        $indexField = (string) ($this->QueryOptions['indexfield'] ?? '');
        $seek = KeysetPager::Build($orderby, $direction, $extra, $this->Options['fields'], $indexField, (string) ($this->QueryOptions['indexexpr'] ?? ''));
        if (null === $seek) {
            $whyNot = ('' === $indexField)
                ? "'indexfield' is empty"
                : "sort column '{$orderby}' (or an extra 'orderby' column) has no 'seek' in its field definition";
        }

        return $seek;
    }

    /**
     * DebugMode text for seek paging: the queries about to run and whether seek is in use.
     */
    private function SeekDebugText(string $query, string $countQuery, ?array $plan, ?array $cursor, ?string $whyNot): string
    {
        if (null !== $plan) {
            $cursorState = !isset($_GET['cur']) ? 'none' : (null !== $cursor ? 'valid' : 'ignored, made for another sort/filter');
            $mode = "seek, mode {$plan['mode']}, skip {$plan['skip']}, limit {$plan['limit']}, cursor {$cursorState}";
        } else {
            $mode = 'offset (seek not used: ' . ($whyNot ?? 'allowpaging is off') . ')';
        }

        return "Paging Mode : {$mode}\r\nData Query : {$query}\r\nPaging Query : {$countQuery}\r\n";
    }

    /**
     * Count of the whole filtered set: the lean pagingquery when given (what the pager always counted).
     */
    private function SeekCountQuery(): string
    {
        $q = $this->QueryOptions;
        if ('' !== trim((string) ($q['pagingquery'] ?? ''))) {
            return $q['pagingquery'] . $q['whereconditions'] . ($q['pagingqueryend'] ?? '');
        }

        return 'Select count(*) from (' . $q['basicquery'] . $q['whereconditions'] . ') t';
    }

    /**
     * @return string
     */
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

        $filterdata = $this->Options['filterdata'] ?? [];

        foreach ($this->Options['fields'] as $field => $val) {
            switch ($val['type']) {
                case 'longstring':
                    $count = count($this->Options['fields']);
                    ++$count;
                    $count = (int) ((2 / $count) * 100);
                    $listing .= '<th style="width:' . $count . '%;">';

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

            $filter = $this->GetColumnFilter($val);
            if (null !== $filter) {
                $listing .= $filter->Render($this->Options['gridid'] . '-filter-' . $field, $filterdata);
            }
            $listing .= '</th>';
        }

        if (!$RemoveFieldOption) {
            $listing .= '	<th>&nbsp;</th>';
        }
        $listing .= '</tr>';

        return $listing;
    }

    /**
     * Column's 'filter' (IGridFilter) wins; otherwise the legacy choice by column type.
     */
    private function GetColumnFilter(array $val): ?IGridFilter
    {
        if (($val['filter'] ?? null) instanceof IGridFilter) {
            return $val['filter'];
        }

        return match ($val['type']) {
            'date', 'datetime' => new DateFilter('daterange' == ($val['filtertype'] ?? '')),
            'onoff', 'flag' => null,
            default => new TextFilter(),
        };
    }
}

Grid::$UI = new GridBootstrap();
