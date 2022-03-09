<?php

namespace TAS\Core;

/**
 * Basic database handling class.
 *
 * @author TAS Team Gupta
 */
class DB
{
    /**
     * Character set.
     *
     * @var string
     */
    public $Charset = 'utf8';

    /**
     * Collation.
     *
     * @var string
     */
    public $Collation = 'utf8mb4_bin';

    /**
     * Array of all error captured during operations.
     *
     * @var array
     */
    public $lastError = [];

    /**
     * Database object after connection.
     *
     * @var object
     */
    public $MySqlObject;

    /**
     * Mysql Version.
     */
    public $MysqlVersion;

    /**
     * defines is Queries needs to be printed as out for debugging.
     *
     * @var bool
     */
    public $Debug = false;
    /**
     * Server IP/Name for database connection.
     *
     * @var string
     */
    private $Server = 'localhost';

    /**
     * Username for database.
     *
     * @var string
     */
    private $User = 'root';

    /**
     * Password for database connection.
     *
     * @var string
     */
    private $Password = '';

    /**
     * SQL Error as catched for any operation.
     *
     * @var string
     */
    private $SQLERROR = '';

    /**
     * Database name to connect.
     *
     * @var string
     */
    private $DBName = 'demo';

    /**
     * Returns if database is connected or not.
     *
     * @var bool
     */
    private $_isconnected = false;

    // Constuctor function
    public function __construct($server = 'localhost', $user = 'root', $password = '', $DBname = 'demo')
    {
        $this->Server = $server;
        $this->User = $user;
        $this->Password = $password;
        $this->DBName = $DBname;
        $this->MySqlObject = null;
        $this->MysqlVersion = 5;
    }

    /**
     * Public Function to connect to db.
     */
    public function Connect()
    {
        $this->CleanError();
        $this->MySqlObject = new \mysqli($this->Server, $this->User, $this->Password, $this->DBName);
        if ($this->MySqlObject->connect_errno) {
            $this->SetError('Connect Error ('.$this->MySqlObject->connect_errno.') '.$this->MySqlObject->connect_error);
            $this->_isconnected = false;

            throw new \Exception('Unable to connect to database');
        }
        $this->_isconnected = true;

        $this->MySqlObject->set_charset($this->Charset);
        $this->MySqlObject->query('SET collation_connection = '.$this->Collation);
        $_v = $this->MySqlObject->server_info;
        $_vs = explode('.', $_v);
        $this->MysqlVersion = $_vs[0];

        return true;
    }

    /**
     * Return true if connected.
     */
    public function IsConnected()
    {
        if ($this->_isconnected) {
            $this->MySqlObject->ping();
        }

        return $this->_isconnected;
    }

    public function LastError()
    {
        return $this->SQLERROR;
    }

    public function LastErrors()
    {
        return $this->lastError;
    }

    /**
     * Clean the database object and clear the error log.
     */
    public function CloseDB()
    {
        $this->MySqlObject->close();
        $this->_isconnected = false;
        unset($this->lastError);
    }

    public function Escape($str)
    {
        return $this->MySqlObject->real_escape_string($str);
    }

    public function ClearStoredResults()
    {
        while ($this->MySqlObject->more_results() && $this->MySqlObject->next_result()) {
            if ($l_result = $this->MySqlObject->store_result()) {
                $l_result->free();
            }
        }
    }

    /**
     * Main function to execute any sql query. In case you use Store procedure use ClearStoredResults to clear result set.
     *
     * @param string $query
     */
    public function Execute($query)
    {
        if (!$this->IsConnected()) {
            $this->SetError('Database is not connected');

            return false;
        }
        $this->CleanError();
        if (empty(trim($query))) {
            $this->SetError('Attempt to execute blank query');

            return false;
        }
        $result = $this->MySqlObject->query($query, MYSQLI_STORE_RESULT);
        if (false === $result) {
            $this->SetError("<br />Error in Query {$query} is ".$this->MySqlObject->error.'::'.print_r($result, true));

            return false;
        }
        $this->MySqlObject->store_result();

        return $result;
    }

    /**
     * Execute Query and return first row, first column if success else it returns false.
     *
     * @param string $query
     */
    public function ExecuteScalar($query)
    {
        $result = $this->Execute($query);
        if ($result) {
            $this->CleanError();
            if ($result->num_rows > 0) {
                $rs = @$result->fetch_row() or $this->SetError("<br />Error in Query {$query} is ".$this->MySqlObject->error);

                return $rs[0] ?? false;
            }
            $this->SetError('Empty recordset returned');

            return false;
        }
        $this->SetError('No Record found');

        return false;
    }

    /**
     * Returns first row from given query.
     *
     * @param [type] $query
     */
    public function ExecuteScalarRow($query)
    {
        $result = $this->Execute($query);
        if ($result) {
            $this->CleanError();
            if ($result->num_rows > 0) {
                $rs = $this->FetchArray($result);

                return ($rs) ? $rs : false;
            }
            $this->SetError('Empty recordset returned');

            return false;
        }
        $this->SetError('No Record found');

        return false;
    }

    /**
     * Shorthand function to comvert a recordset to array.
     */
    public function ExecuteAll(string $query): array
    {
        $result = $this->Execute($query);

        return (false !== $result && static::Count($result) > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * @deprecated 2.0.0
     *
     *  Return number of rows in give recordset. Use Static function DB::Count instead for shorter syntax.
     *
     * @param [type] $result
     */
    public function RowCount($result)
    {
        $this->CleanError();
        $output = @$result->num_rows or $this->SetError($this->MySqlObject->error);

        return (is_numeric($output)) ? ($output) : -1;
    }

    /**
     * Static Function as replacement of RowCount.
     *
     * @param $result
     */
    public static function Count($result)
    {
        $output = @$result->num_rows;

        return (is_numeric($output)) ? $output : -1;
    }

    /**
     * Return last Generated ID (autoincrement) from last insert.
     */
    public function GeneratedID()
    {
        return $this->MySqlObject->insert_id;
    }

    /**
     * Reset the Recordset to 0th position for reiteration.
     *
     * @param [type] $rs
     */
    public static function Reset(\mysqli_result $rs)
    {
        if (is_null($rs)) {
            return;
        }
        @$rs->data_seek(0);
    }

    // Function to fetch the array value
    public function FetchArray($result)
    {
        $this->CleanError();

        try {
            $row = @$result->fetch_array();
            if (!is_array($row)) {
                $row = false;
            }
        } catch (\Exception $ex) {
            $this->SetError($this->MySqlObject->error." \r\n".$ex->getMessage());

            return false;
        }

        return (is_array($row)) ? $row : false;
    }

    // Function to fetch the array value
    public function Fetch($result)
    {
        $this->CleanError();

        try {
            $row = $result->fetch_assoc();
            if (!is_array($row)) {
                $row = false;
            }
        } catch (\Exception $ex) {
            $this->SetError($this->MySqlObject->error." \r\n".$ex->getMessage());

            return false;
        }

        return (is_array($row)) ? $row : false;
    }

    public function FetchRow($result)
    {
        $this->CleanError();

        try {
            $row = $result->fetch_row();
            if (!is_array($row)) {
                $row = false;
            }
        } catch (\Exception $ex) {
            $this->SetError($this->MySqlObject->error." \r\n".$ex->getMessage());

            return false;
        }

        return (is_array($row)) ? $row : false;
    }

    /**
     * function to insert record by taking array in following form
     * $value[index] = value : where $value is array name to parse,
     * index = db column name, and value is vlaue to insert.
     *
     * @param mixed $tablename
     * @param mixed $values
     * @param mixed $datatype
     */
    public function Insert($tablename, $values, $datatype = '')
    {
        $query = '';
        if (empty($tablename)) {
            return false;
        }
        if (!is_array($values)) {
            return false;
        }

        $keys = array_keys($values);
        foreach (array_keys($values) as $k) {
            $refs[] = &$values[$k];
        }

        $datatype = empty($datatype) ? static::GetDataString($tablename, $values) : $datatype;

        $query = "INSERT INTO `{$tablename}` (".implode(',', $keys).') VALUES ('.str_repeat('?,', (count($keys) - 1)).'?)';
        if ($this->Debug) {
            echo "\n<br>Insert Query is : ".$query;
        }
        $stmt = $this->MySqlObject->prepare($query);
        if (is_bool($stmt) && false === $stmt) {
            $this->SetError('Query preparation fails possible mismatch columns (Error thrown: '.$this->MySqlObject->error.')');

            \TAS\Core\Log::AddEvent([
                'message' => 'Database Insert Prepare Failed !!!',
                'query' => $query,
                'error' => $this->MySqlObject->error,
            ], 'normal');

            return false;
        }

        array_unshift($refs, $datatype);
        $params = array_merge([$datatype], $values);
        call_user_func_array([&$stmt, 'bind_param'], $refs);
        $stmt->execute();
        if ('' == $this->MySqlObject->error) {
            return true;
        }
        \TAS\Core\Log::AddEvent([
            'message' => 'Database Insert Failed !!!',
            'query' => $query,
            'error' => $this->MySqlObject->error,
        ], 'normal');

        $this->SetError($this->MySqlObject->error);
        $this->CleanError();

        return false;
    }

    /**
     * function to update record by taking array in following form
     * $value[index] = value : where $value is array name to parse,
     * index = db column name, and value is vlaue to insert.
     *
     * @param mixed $tablename
     * @param mixed $values
     * @param mixed $editid
     * @param mixed $editfield
     * @param mixed $datatype
     */
    public function Update($tablename, $values, $editid, $editfield, $datatype = '')
    {
        $query = '';
        $refs = [];
        if (empty($tablename)) {
            return false;
        }
        if (!is_array($values)) {
            return false;
        }
        $keys = array_keys($values);

        $columnlist = [];
        foreach (array_keys($values) as $k) {
            $refs[] = &$values[$k];
            $columnlist[] = $k.'=?';
        }

        $datatype = empty($datatype) ? static::GetDataString($tablename, $values) : $datatype;

        $query = "Update `{$tablename}` set ".implode(',', $columnlist)." where `{$editfield}`=?";
        $refs[] = &$editid;
        $datatype .= is_numeric($editid) ? 'i' : 's';

        if ($this->Debug) {
            echo "\n<br>Update Query is : ".$query."\r\n<br \\>".print_r($refs, true);
        }
        $stmt = $this->MySqlObject->prepare($query);
        if (is_bool($stmt) && false === $stmt) {
            $this->SetError('Query preparation fails possible mismatch columns (Error thrown: '.$this->MySqlObject->error.')');
            \TAS\Core\Log::AddEvent([
                'message' => 'Database Update Prepare Failed !!!',
                'query' => $query,
                'error' => $this->MySqlObject->error,
            ], 'normal');

            return false;
        }
        array_unshift($refs, $datatype);

        $params = array_merge([$datatype], $values);
        call_user_func_array([&$stmt, 'bind_param'], $refs);
        $stmt->execute();
        if ('' == $this->MySqlObject->error) {
            return true;
        }
        \TAS\Core\Log::AddEvent([
            'message' => 'Database Update Failed !!!',
            'query' => $query,
            'error' => $this->MySqlObject->error,
        ], 'normal');

        $this->SetError($this->MySqlObject->error);
        $this->CleanError();

        return false;
    }

    public function InsertUpdate($table, $values, $datatype = '')
    {
        if ('' != $table) {
            if (is_array($values)) {
                $keys = array_keys($values);

                $datatype = empty($datatype) ? static::GetDataString($tablename, $values) : $datatype;

                if (strlen($datatype) != count($keys)) {
                    $this->SetError('Error in Preparing Query not all column founds');
                    $this->CleanError();

                    return false;
                }

                $query = "INSERT INTO {$table} (".implode(',', $keys).') VALUES ('.str_repeat('?,', (count($keys) - 1)).'?)
				ON DUPLICATE KEY UPDATE ';

                $refs = [];
                $ref2 = [];
                $queryAddon = [];
                $datatype .= $datatype;
                $refs[] = &$datatype;
                foreach ($values as $key => $value) {
                    $queryAddon[] = "{$key}=?";
                    $refs[] = &$values[$key];
                    $ref2[] = &$values[$key];
                }

                $refs = array_merge($refs, $ref2);
                $query .= implode(',', $queryAddon);

                $stmt = $this->MySqlObject->prepare($query);
                $params = array_merge([
                    $datatype,
                ], $values);

                call_user_func_array([
                    &$stmt,
                    'bind_param',
                ], $refs);

                $stmt->execute();
                if ('' == $this->MySqlObject->error) {
                    return true;
                }
                $this->SetError($this->MySqlObject->error);
                $this->CleanError();

                return false;
            }
        } else {
            return false;
        }
    }

    // function to REPLACE record by taking array in following form
    // $value[index] = value : where $value is array name to parse,
    // index = db column name, and value is vlaue to REPLACE
    public function ReplaceArrayById($tablename, $values)
    {
        if ('' != $tablename) {
            if (is_array($values)) {
                $query = "REPLACE into {$tablename} set ";
                $valuecount = count($values);
                $ctr = 1;
                foreach ($values as $index => $value) {
                    $query .= " {$index} = '".$this->MySqlObject->real_escape_string($value)."'";
                    if ($valuecount > $ctr) {
                        $query .= ', ';
                    }
                    ++$ctr;
                }
                // echo $query; //die;
                if ($this->Debug) {
                    echo "\n<br>REPLACE Query is : ".$query;
                }
                // echo $query;die;
                $this->CleanError();
                $output = $this->Execute($query);
                $output = (true === $output) ? true : false;
                if (!$output) {
                    $this->SetError('Error in Query :'.$query.' with error  '.$this->MySqlObject->error);
                    $this->CleanError();
                }

                return $output;
            }
        } else {
            return false;
        }
    }

    /**
     * @incomplete
     * Insert in Bulk Query.
     * This do not validate data. It expect you to send all column in same order.
     *
     * @param string $tablename
     *                            name of the table to insert into
     * @param array  $values
     *                            Array of data in array( array("columnname1"=> "data", "column2"=>"Data" ) )
     * @param string $failonError
     *                            set to true if you want to break Query if error encount or continue ignoring error row. default is false, and not in use as of now.
     */
    public function InsertBulk($tablename, $values, $failonError = false)
    {
        $this->CleanError();
        if (!is_array($values)) {
            $this->SetError('Incorrect Argument value for InsertBulk');

            return false;
        }

        // @todo: We can fetch column from DB To compare here.
        // $cName = DB::GetColumnsName($tablename);

        $rows = [];

        foreach ($values as $rowid => $rowdata) {
            if (true) { // @todo: how to validate the data here and column counts.
                $cName = array_keys($rowdata);
                $rows[] = "('".implode("','", $rowdata)."')";
            } else {
                $this->SetError('InsertBulk:: Some data is not valid; '.print_r($rowdata, true));
                if ($failonError) {
                    return false;
                }
            }
        }

        $query = 'Insert into '.$tablename.' (`'.implode('`,`', $cName).'`) VALUES '.implode(',', $rows);

        return $this->Execute($query);
    }

    /**
     * Insert multiple array.
     *
     * @param mixed $tablename
     * @param mixed $values
     */
    public function InsertMultiArray($tablename, $values = [])
    {
        $this->CleanError();
        if (is_array($values)) {
            $columnsList = [];
            $QueryParts = [];
            $Columns = '';
            $query = '';

            if (isset($values['data']) && is_array($values['data'])) {
                foreach ($values['data'] as $row) {
                    if (is_array($row)) {
                        $row = array_merge($row, $values['common']);
                        $QueryParts[] = "('".implode("','", $row)."')";
                    }
                }
                if ('' == $Columns && count($values['data']) > 0) {
                    $rowColumn = array_merge(array_keys($values['data'][0]), array_keys($values['common']));
                    $Columns = '('.implode(',', $rowColumn).')';
                }
                $query = "insert into {$tablename}".$Columns.' values '.implode(',', $QueryParts);
                $this->Execute($query);
            }

            if ($this->Debug) {
                echo "\n<br>Insert Query is : ".$query;
            }

            return true;
        }

        return false;
    }

    /**
     * Replace multiple array.
     *
     * @param mixed $tablename
     * @param mixed $values
     */
    public function ReplaceMultiArrayByID($tablename, $values = [])
    {
        $this->CleanError();
        if (is_array($values)) {
            $columnsList = [];
            $QueryParts = [];
            $Columns = '';
            $query = '';
            if (isset($values['data']) && is_array($values['data'])) {
                foreach ($values['data'] as $row) {
                    if (is_array($row)) {
                        $row = array_merge($row, $values['common']);
                        $QueryParts[] = "('".implode("','", $row)."')";
                    }
                }
                if ('' == $Columns && count($values['data']) > 0) {
                    $rowColumn = array_merge(array_keys($values['data'][0]), array_keys($values['common']));
                    $Columns = '('.implode(',', $rowColumn).')';
                }
                $query = "REPLACE into {$tablename} ".$Columns.' values '.implode(',', $QueryParts);
                $this->Execute($query);
            }

            if ($this->Debug) {
                echo "\n<br>Replace Query is : ".$query;
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the database Record set with given condition.
     *
     * @param mixed $table
     * @param mixed $orderby
     * @param mixed $where
     */
    public function DBRecordSet($table, $orderby = '', $where = 'status = 1')
    {
        $query = 'Select * from '.$GLOBALS['Tables'][$table];
        if ('' != trim($where)) {
            $query .= ' where '.$where;
        }
        if ('' != trim($orderby)) {
            $query .= ' order by '.$orderby;
        }
        if ($this->Debug) {
            echo 'Query is'.$query;
        }

        return $this->Execute($query);
    }

    /**
     * Delete from given table on given ID.
     *
     * @param mixed $table
     * @param mixed $id
     * @param mixed $idfield
     */
    public function Delete($table, $id, $idfield)
    {
        if (!is_numeric($id) || $id < 0) {
            return false;
        }
        $id = (int) $id;

        return $this->Execute('Delete from '.$table." Where {$idfield} = {$id}");
    }

    public static function Columns($result)
    {
        $fields = [];
        if ($result && \TAS\Core\DB::Count($result) >= 0) {
            $fields = @$result->fetch_fields();
        }

        return $fields;
    }

    /**
     * Get all Columns of given table in form of array.
     *
     * @param string $table
     *                      Either DB Based Table name or Index of GLOBAL TABLE
     */
    public static function GetColumns($table)
    {
        if (isset($GLOBALS['Tables'][$table])) { // We can get either the DB base Table name or our Table Array index.
            $table = $GLOBALS['Tables'][$table];
        }
        $f = $GLOBALS['db']->Execute('show Columns from '.$table);
        $fields = [];
        foreach ($f as $row) {
            $fields[] = $row;
        }

        return $fields;
    }

    public function FirstColumnArray($query)
    {
        $rs = $this->Execute($query);
        if ($this->RowCount($rs) > 0) {
            $output = [];
            while ($row = $this->FetchRow($rs)) {
                $output[] = $row['0'];
            }

            return $output;
        }
        $this->SetError('No record found for FirstColumnArray Creation');

        return [];
    }

    public static function GetTableInformation($tablename)
    {
        $x = \TAS\Core\DB::GetColumns($tablename);

        $TableArray = [];
        foreach ($x as $i => $k) {
            $TableArray[$k['Field']] = [];
            $TableArray[$k['Field']]['name'] = $k['Field'];
            if ('bigint' == substr($k['Type'], 0, 6) || 'int' == substr($k['Type'], 0, 3)) {
                $TableArray[$k['Field']]['type'] = 'int';
            } elseif ('float' == substr($k['Type'], 0, 5) || 'double' == substr($k['Type'], 0, 6) || 'real' == substr($k['Type'], 0, 4) || 'decimal' == substr($k['Type'], 0, 7)) {
                $TableArray[$k['Field']]['type'] = 'float';
            } elseif ('datetime' == substr($k['Type'], 0, 8)) {
                $TableArray[$k['Field']]['type'] = 'datetime';
            } elseif ('date' == substr($k['Type'], 0, 4)) {
                $TableArray[$k['Field']]['type'] = 'date';
            } elseif ('text' == substr($k['Type'], 0, 4) || 'mediumtext' == $k['Type'] || 'tinytext' == $k['Type'] || 'longtext' == $k['Type']) {
                $TableArray[$k['Field']]['type'] = 'text';
            } else {
                $TableArray[$k['Field']]['type'] = 'string';
            }

            $size = preg_match('/[^\\(]*\\((.*)\\)[^\\)]*/', $k['Type'], $matches, PREG_OFFSET_CAPTURE);
            if (count($matches) > 1 && isset($matches[1][0])) {
                $size = $matches[1][0];
            }
            $TableArray[$k['Field']]['size'] = $size;
            $TableArray[$k['Field']]['Null'] = $k['Null'];
        }

        return $TableArray;
    }

    public static function GetAutoIncrementID($tablename)
    {
        if ($GLOBALS['db']->MysqlVersion >= 8) {
            $GLOBALS['db']->Execute('SET information_schema_stats_expiry = 0; ');
        }
        $result = $GLOBALS['db']->ExecuteScalarRow("show table status where Name = '".$tablename."'");

        return $result['Auto_increment'];
    }

    public static function ToJSON(\mysqli_result $recordset)
    {
        if (\is_null($recordset) || is_bool($recordset)) {
            return json_encode([]);
        }
        DB::Reset($recordset);

        return json_encode($recordset->fetch_all(MYSQLI_ASSOC));
    }

    private function CleanError()
    {
        if ('' != trim($this->SQLERROR)) {
            $this->lastError[] = $this->SQLERROR;
        }
        $this->SQLERROR = '';
    }

    private function SetError($error)
    {
        if ($this->Debug) {
            \TAS\Core\Log::AddEvent([
                'message' => 'Database Query Fail in '.debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'],
                'error' => $error,
            ], 'normal');
        }

        $this->CleanError();
        $this->SQLERROR = $error;
    }

    /**
     * Data Type of given column.
     */
    private function GetDataString(string $tablename, array $values)
    {
        $datatype = '';
        $Columns = \TAS\Core\DB::GetColumns($tablename);
        foreach (array_keys($values) as $k) {
            reset($Columns);
            foreach ($Columns as $field) {
                if ($field['Field'] == $k) {
                    $type = preg_replace('/(\([0-9\,]*\))/i', '', $field['Type']);

                    switch ($field['Type']) {
                        case 'bigint':
                            $datatype .= 'i';

                            break;

                        case 'datetime':
                            $datatype .= 's';

                            break;

                        case 'decimal':
                            $datatype .= 'd';

                            break;

                        default:
                            $datatype .= 's';
                    }
                }
            }
        }

        return $datatype;
    }
}
