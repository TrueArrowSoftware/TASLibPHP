<?php

namespace TAS\Core;

use TAS\Core\Async\AsyncQuery;
use TAS\Core\Async\DBPool;
use TAS\Core\Async\FiberRunner;

class CSV
{
    /**
     * Write one CSV row: real CSV quoting (a " or , inside a value no longer breaks the row), and a text cell
     * starting with = + - @ tab or CR gets a leading ' so Excel/Calc show it instead of running it as a formula.
     * Numbers like -5 or +5 are left alone; text such as "+91 98765" is not a number and gets the '.
     *
     * @param resource $fh
     */
    public static function WriteRow($fh, array $cells): void
    {
        foreach ($cells as $i => $cell) {
            $cell = (string) ($cell ?? '');
            if ('' !== $cell && str_contains("=+-@\t\r", $cell[0]) && !is_numeric($cell)) {
                $cells[$i] = "'" . $cell;
            }
        }
        fputcsv($fh, $cells, ',', '"', '');
    }

    /**
     * Header row from $fields (column objects, field definitions with 'name', or plain names); returns their keys.
     *
     * @param resource $fh
     */
    private static function WriteHeader($fh, array $fields): array
    {
        $names = [];
        foreach ($fields as $field) {
            $names[] = is_object($field) ? $field->name : (is_array($field) ? $field['name'] : $field);
        }
        self::WriteRow($fh, $names);

        return array_keys($fields);
    }

    /**
     * A column's value: for a 'globalarray' field its label from that global array (the value itself when unlisted).
     */
    private static function CellValue($row, $column, $field)
    {
        if (is_array($field) && 'globalarray' === ($field['type'] ?? '') && null != $row[$column] && isset($field['arrayname'])) {
            return $GLOBALS[$field['arrayname']][$row[$column]] ?? $row[$column];
        }

        return $row[$column];
    }

    /**
     * @param $SQLQuery
     * @param $filename
     * @param $tagname
     * @param array $param
     * @return bool
     */
    public static function CreateCSV($SQLQuery, $filename, $tagname, array $param = [])
    {
        [$orderby, $orderdirection] = DataFormat::ReturnSortOrder($tagname, (string) ($SQLQuery['defaultorderby'] ?? ''), (string) ($SQLQuery['defaultsortdirection'] ?? ''), $param['fields'] ?? [], (array) ($SQLQuery['orderby'] ?? []));

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

        // a name nobody chose, removed once sent: $filename is only the download name (it was a path under cache/)
        $filepath = tempnam($GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache', 'csv');
        try {
            $ok = self::ExportCSV($query, $filepath, $param['fields']);
            if ($ok) {
                \TAS\Core\Web::DownloadHeader(basename((string) $filename));
                readfile($filepath);
            }
        } finally { // also when the query throws
            @unlink($filepath);
        }
        if ($ok) {
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
     * @return bool
     */
    public static function ExportCSV($SQLQuery, $filename = '', array $fields = [])
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
        $fieldindex = self::WriteHeader($fh, $fields);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fieldindex);
        }
        foreach ($rs as $row) {
            $cells = [];
            foreach ($fieldindex as $val) {
                $cells[] = self::CellValue($row, $val, $fields[$val]);
            }
            self::WriteRow($fh, $cells);
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

        $fieldindex = self::WriteHeader($fh, $fields);

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
                        $cells = [];
                        foreach ($fieldindex as $val) {
                            $cells[] = self::CellValue($row, $val, $fields[$val] ?? null);
                        }
                        self::WriteRow($fh, $cells);
                    }
                    $rs->free();
                }
            }
        }

        fclose($fh);

        return true;
    }
}
