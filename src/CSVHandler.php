<?php

namespace TAS\Core;

use TAS\Core\Async\AsyncQuery;
use TAS\Core\Async\DBPool;
use TAS\Core\Async\FiberRunner;

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

    /**
     * Export CSV using parallel chunked DB queries via Fibers.
     *
     * Splits the query into chunks with LIMIT/OFFSET, runs them in parallel,
     * and writes results to a single file. Falls back to sequential ExportCSV
     * if no DBPool is available.
     *
     * @param string $SQLQuery    Base SQL query (without LIMIT)
     * @param string $filename    Physical path of csv file
     * @param array  $fields      List of fields to put in csv
     * @param int    $chunkSize   Rows per chunk (default 5000)
     * @return bool
     */
    public static function ExportCSVAsync(string $SQLQuery, string $filename = '', array $fields = [], int $chunkSize = 5000): bool
    {
        if (!isset($GLOBALS['dbpool']) || !($GLOBALS['dbpool'] instanceof DBPool)) {
            return self::ExportCSV($SQLQuery, $filename, $fields);
        }

        $pool = $GLOBALS['dbpool'];

        // First get total count to determine chunks
        $countQuery = 'SELECT COUNT(*) FROM (' . $SQLQuery . ') _csv_count';
        $db = $pool->acquire();
        $totalRows = (int) $db->ExecuteScalar($countQuery);
        $pool->release($db);

        if ($totalRows < 1) {
            return false;
        }

        // If small enough, just do it sequentially
        if ($totalRows <= $chunkSize) {
            return self::ExportCSV($SQLQuery, $filename, $fields);
        }

        $fh = fopen($filename ?: 'export.csv', 'w+');

        // Get fields from first chunk if not provided
        if (!is_array($fields) || 0 === count($fields)) {
            $dbTemp = $pool->acquire();
            $rsTemp = $dbTemp->Execute($SQLQuery . ' LIMIT 1');
            $fields = \TAS\Core\DB::Columns($rsTemp);
            $pool->release($dbTemp);
        }

        // Write header
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
            $csvHeader .= ',"' . $fieldname . '" ';
        }
        $csvHeader = trim($csvHeader, ',') . "\n";
        fwrite($fh, $csvHeader);

        // Build chunked queries
        $chunks = ceil($totalRows / $chunkSize);
        $maxParallel = min($chunks, $pool->getMaxConnections());

        // Process chunks in batches
        for ($batch = 0; $batch < $chunks; $batch += $maxParallel) {
            $queries = [];
            $batchEnd = min($batch + $maxParallel, $chunks);

            for ($i = $batch; $i < $batchEnd; $i++) {
                $offset = $i * $chunkSize;
                $queries['chunk_' . $i] = $SQLQuery . " LIMIT {$chunkSize} OFFSET {$offset}";
            }

            $chunkResults = AsyncQuery::runParallel($queries, $pool);

            // Write chunks in order
            for ($i = $batch; $i < $batchEnd; $i++) {
                $rs = $chunkResults['chunk_' . $i] ?? null;
                if ($rs && $rs instanceof \mysqli_result) {
                    while ($row = $rs->fetch_array()) {
                        $dataline = '';
                        foreach ($fieldindex as $key => $val) {
                            if (isset($fields[$val]) && is_array($fields[$val])) {
                                switch ($fields[$val]['type'] ?? '') {
                                    case 'globalarray':
                                        if (null != $row[$val] && isset($fields[$val]['arrayname'])) {
                                            $dataline .= ',"' . ($GLOBALS[$fields[$val]['arrayname']][$row[$val]] ?? $row[$val]) . '" ';
                                        } else {
                                            $dataline .= ',"' . $row[$val] . '" ';
                                        }
                                        break;
                                    default:
                                        $dataline .= ',"' . $row[$val] . '" ';
                                }
                            } else {
                                $dataline .= ',"' . $row[$val] . '" ';
                            }
                        }
                        $dataline = trim($dataline, ',') . "\n";
                        fwrite($fh, $dataline);
                    }
                    $rs->free();
                }
            }
        }

        fclose($fh);

        return true;
    }
}
