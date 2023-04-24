<?php

namespace TAS\Core;

class CSV
{
    public static function CreateCSV($SQLQuery, $filename, $tagname, $param = [])
    {
        $orderby = ((isset($_GET['orderby'])) ? $_GET['orderby'] : ($_SESSION[$tagname.'_orderby'] ?? $SQLQuery['defaultorderby']));
        $orderdirection = ((isset($_GET['direction'])) ? $_GET['direction'] : ($_SESSION[$tagname.'_direction'] ?? $SQLQuery['defaultsortdirection']));

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
        $query = $SQLQuery['basicquery'].$SQLQuery['whereconditions']." order by {$orderby} {$orderdirection} {$sortstring} ";
        $rs = $GLOBALS['db']->Execute($query);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo $query;
        }

        $filepath = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$filename;

        if (self::ExportCSV($query, $filepath, $param['fields'])) {
            \TAS\Core\Web::DownloadHeader($filename);
            readfile($filepath);

            exit;
        }

        return false;
    }

    /**
     * Generic function to create CSV file on disk.
     *
     * @param string $SQLQuery
     *                         Database SQL Query
     * @param string $filename
     *                         Physical path of csv file
     * @param array  $fields
     *                         list of fields to put in csv
     */
    public static function ExportCSV($SQLQuery, $filename = '', $fields = [])
    {
        $rs = $GLOBALS['db']->Execute($SQLQuery);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo 'Total Record found'.\TAS\Core\DB::Count($rs);
        }
        if (\TAS\Core\DB::Count($rs) < 1) {
            return false;
        }
        $fh = '';
        if ('' == $filename) {
            $fh = fopen('export.csv', 'w+');
        } else {
            $fh = fopen($filename, 'w+');
        }
        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fields);
        }
        if (!is_array($fields) || 0 == count($fields)) {
            $fields = \TAS\Core\DB::Columns($rs);
        }

        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fields);
        }
        $csvHeader = '';
        $fieldindex = [];
        foreach ($fields as $index => $field) {
            if (is_object($field)) {
                $fieldname = $field->name;
            } elseif (is_array($field)) {
                $fieldname = $field['name'];
            } else {
                $fieldname = $field;
            }
            $fieldindex[] = $index;
            $csvHeader .= ',"'.$fieldname.'" ';
        }
        $csvHeader = trim($csvHeader, ',');
        $csvHeader .= "\n";

        fwrite($fh, $csvHeader);
        unset($csvHeader);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fieldindex);
        }
        foreach ($rs as $row) {
            $dataline = '';
            foreach ($fieldindex as $key => $val) {
                if ($fields[$val]) {
                    switch ($fields[$val]['type']) {
                        case 'globalarray':
                            if (null != $row[$val]) {
                                if (isset($fields[$val]['arrayname'])) {
                                    $dataline .= ',"'.$GLOBALS[$fields[$val]['arrayname']][$row[$val]].'" ';
                                } else {
                                    $dataline .= ',"'.$row[$val].'" ';
                                }
                            }

                            break;

                        default:
                            $dataline .= ',"'.$row[$val].'" ';
                    }
                } else {
                    $dataline .= ',"'.$row[$val].'" ';
                }
            }
            $dataline = trim($dataline, ',')."\n";
            fwrite($fh, $dataline);
        }
        fclose($fh);

        return true;
    }
}
