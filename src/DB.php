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
     * Returns if database is connected or not.
     *
     * @var bool
     */
    private $_isconnected = false;

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
        } else {
            $this->_isconnected = true;

            $this->MySqlObject->set_charset($this->Charset);
            $this->MySqlObject->query('SET collation_connection = '.$this->Collation);
            $_v = $this->MySqlObject->server_info;
            $_vs = explode('.', $_v);
            $this->MysqlVersion = $_vs[0];

            return true;
        }
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

    private function CleanError()
    {
        if (trim($this->SQLERROR) != '') {
            $this->lastError[] = $this->SQLERROR;
        }
        $this->SQLERROR = '';
    }

    public function LastError()
    {
        return $this->SQLERROR;
    }

    public function LastErrors()
    {
        return $this->lastError;
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
     *
     * @return void
     */
    public function Execute($query)
    {
        if ($this->IsConnected()) {
            $this->CleanError();
            if (trim($query) != '') {
                $result = $this->MySqlObject->query($query, MYSQLI_STORE_RESULT);
                if ($result === false) {
                    $this->SetError("<br />Error in Query $query is ".$this->MySqlObject->error.'::'.print_r($result, true));

                    return false;
                } else {
                    $this->MySqlObject->store_result();

                    return $result;
                }
            } else {
                $this->SetError('Attempt to execute blank query');

                return false;
            }
        } else {
            $this->SetError('Database is not connected');

            return false;
        }
    }

    /**
     * Execute Query and return first row, first column if success else it returns false.
     *
     * @param string $query
     *
     * @return void
     */
    public function ExecuteScalar($query)
    {
        $result = $this->Execute($query);
        if ($result) {
            $this->CleanError();
            if ($result->num_rows > 0) {
                $rs = @$result->fetch_row() or $this->SetError("<br />Error in Query $query is ".$this->MySqlObject->error);

                return isset($rs[0]) ? $rs[0] : false;
            } else {
                $this->SetError('Empty recordset returned');

                return false;
            }
        } else {
            $this->SetError('No Record found');

            return false;
        }
    }

    /**
     * Returns first row from given query.
     *
     * @param [type] $query
     *
     * @return void
     */
    public function ExecuteScalarRow($query)
    {
        $result = $this->Execute($query);
        if ($result) {
            $this->CleanError();
            if ($result->num_rows > 0) {
                $rs = $this->FetchArray($result);

                return ($rs) ? $rs : false;
            } else {
                $this->SetError('Empty recordset returned');

                return false;
            }
        } else {
            $this->SetError('No Record found');

            return false;
        }
    }

    /**
     * @deprecated 2.0.0
     *
     *  Return number of rows in give recordset. Use Static function DB::Count instead for shorter syntax.
     *
     * @param [type] $result
     *
     * @return void
     */
    public function RowCount($result)
    {
        $this->CleanError();
        $output = @$result->num_rows or $this->SetError($this->MySqlObject->error);

        return (is_numeric($output)) ? ($output) : -1;
    }

    /**
     * Static Function as replacement of RowCount.
     * Returns false if error.
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
     *
     * @return void
     */
    public function GeneratedID()
    {
        return $this->MySqlObject->insert_id;
    }

    public function Reset($rs)
    {
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
     */
    public function Insert($tablename, $values, $datatype = '')
    {
        $query = '';
        if ($tablename != '') {
            if (is_array($values)) {
                $keys = array_keys($values);

                foreach (array_keys($values) as $k) {
                    $refs[] = &$values[$k];
                }

                if ($datatype == '') {
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
                }

                $query = "INSERT INTO `$tablename` (".implode(',', $keys).') VALUES ('.str_repeat('?,', (count($keys) - 1)).'?)';
                if ($this->Debug) {
                    echo "\n<br>Insert Query is : ".$query;
                }
                $stmt = $this->MySqlObject->prepare($query);
                array_unshift($refs, $datatype);
                $params = array_merge([
                    $datatype,
                ], $values);
                call_user_func_array([
                    &$stmt,
                    'bind_param',
                ], $refs);
                $stmt->execute();
                if ($this->MySqlObject->error == '') {
                    return true;
                } else {
                    \TAS\Core\Log::AddEvent([
                        'message' => 'Database Insert Failed !!!',
                        'query' => $query,
                        'error' => $this->MySqlObject->error,
                    ], 'normal');

                    $this->SetError($this->MySqlObject->error);
                    $this->CleanError();

                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * function to update record by taking array in following form
     * $value[index] = value : where $value is array name to parse,
     * index = db column name, and value is vlaue to insert.
     */
    public function Update($tablename, $values, $editid, $editfield, $datatype = '')
    {
        $query = '';
        $refs = [];
        if ($tablename != '') {
            if (is_array($values)) {
                $keys = array_keys($values);

                $columnlist = [];
                foreach (array_keys($values) as $k) {
                    $refs[] = &$values[$k];
                    $columnlist[] = $k.'=?';
                }

                if ($datatype == '') {
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
                }

                $query = "Update `$tablename` set ".implode(',', $columnlist)." where `$editfield`=?";
                $refs[] = &$editid;
                $datatype .= is_numeric($editid) ? 'i' : 's';

                if ($this->Debug) {
                    echo "\n<br>Update Query is : ".$query."\r\n<br \>".print_r($refs, true);
                }
                $stmt = $this->MySqlObject->prepare($query);
                array_unshift($refs, $datatype);

                $params = array_merge([
                    $datatype,
                ], $values);
                call_user_func_array([
                    &$stmt,
                    'bind_param',
                ], $refs);
                $stmt->execute();
                if ($this->MySqlObject->error == '') {
                    return true;
                } else {
                    \TAS\Core\Log::AddEvent([
                        'message' => 'Database Update Failed !!!',
                        'query' => $query,
                        'error' => $this->MySqlObject->error,
                    ], 'normal');

                    $this->SetError($this->MySqlObject->error);
                    $this->CleanError();

                    return false;
                }
            }
        } else {
            $this->SetError('Invalid Table name.');
            $this->CleanError();

            return false;
        }
    }

    public function InsertUpdate($table, $values, $datatype = '')
    {
        if ($table != '') {
            if (is_array($values)) {
                $keys = array_keys($values);
                if ($datatype == '') {
                    $Columns = \TAS\Core\DB::GetColumns($table);
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
                }
                if (strlen($datatype) != count($keys)) {
                    $this->SetError('Error in Preparing Query not all column founds');
                    $this->CleanError();

                    return false;
                }

                $query = "INSERT INTO $table (".implode(',', $keys).') VALUES ('.str_repeat('?,', (count($keys) - 1)).'?)
				ON DUPLICATE KEY UPDATE ';

                $refs = [];
                $ref2 = [];
                $queryAddon = [];
                $datatype .= $datatype;
                $refs[] = &$datatype;
                foreach ($values as $key => $value) {
                    $queryAddon[] = "$key=?";
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
                if ($this->MySqlObject->error == '') {
                    return true;
                } else {
                    $this->SetError($this->MySqlObject->error);
                    $this->CleanError();

                    return false;
                }
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
        if ($tablename != '') {
            if (is_array($values)) {
                $query = "REPLACE into $tablename set ";
                $valuecount = count($values);
                $ctr = 1;
                foreach ($values as $index => $value) {
                    $query .= " $index = '".$this->MySqlObject->real_escape_string($value)."'";
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
                $output = ($output === true) ? true : false;
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
     */
    public function InsertMultiArray($tablename, $values = [])
    {
        $this->CleanError();
        if (is_array($values)) {
            $columnsList = [];
            $QueryParts = [];
            $Columns = '';

            if (isset($values['data']) && is_array($values['data'])) {
                foreach ($values['data'] as $row) {
                    if (is_array($row)) {
                        $row = array_merge($row, $values['common']);
                        $QueryParts[] = "('".implode("','", $row)."')";
                    }
                }
                if ($Columns == '') {
                    $Columns = '('.implode(',', array_keys($row)).')';
                }
                $query = "insert into $tablename".$Columns.' values '.implode(',', $QueryParts);
                $this->Execute($query);
            }

            if ($this->Debug) {
                echo "\n<br>Insert Query is : ".$query;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Replace multiple array.
     */
    public function ReplaceMultiArrayByID($tablename, $values = [])
    {
        $this->CleanError();
        if (is_array($values)) {
            $columnsList = [];
            $QueryParts = [];
            $Columns = '';

            if (isset($values['data']) && is_array($values['data'])) {
                foreach ($values['data'] as $row) {
                    if (is_array($row)) {
                        $row = array_merge($row, $values['common']);
                        $QueryParts[] = "('".implode("','", $row)."')";
                    }
                }
                if ($Columns == '') {
                    $Columns = '('.implode(',', array_keys($row)).')';
                }
                $query = "REPLACE into $tablename".$Columns.' values '.implode(',', $QueryParts);
                $this->Execute($query);
            }

            if ($this->Debug) {
                echo "\n<br>Replace Query is : ".$query;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the database Record set with given condition.
     */
    public function DBRecordSet($table, $orderby = '', $where = 'status = 1')
    {
        $query = 'Select * from '.$GLOBALS['Tables'][$table];
        if (trim($where) != '') {
            $query .= ' where '.$where;
        }
        if (trim($orderby) != '') {
            $query .= ' order by '.$orderby;
        }
        if ($this->Debug) {
            echo 'Query is'.$query;
        }

        return $this->Execute($query);
    }

    /**
     * Delete from given table on given ID.
     */
    public function Delete($table, $id, $idfield)
    {
        if (!is_numeric($id) || $id < 0) {
            return false;
        }
        $id = (int) $id;

        return $this->Execute('Delete from '.$table." Where $idfield = $id");
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
        while ($row = $GLOBALS['db']->Fetch($f)) {
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
        } else {
            $this->SetError('No record found for FirstColumnArray Creation');

            return [];
        }
    }

    public static function GetTableInformation($tablename)
    {
        $x = \TAS\Core\DB::GetColumns($tablename);

        $TableArray = [];
        foreach ($x as $i => $k) {
            $TableArray[$k['Field']] = [];
            $TableArray[$k['Field']]['name'] = $k['Field'];
            if (substr($k['Type'], 0, 6) == 'bigint' || substr($k['Type'], 0, 3) == 'int') {
                $TableArray[$k['Field']]['type'] = 'int';
            } elseif (substr($k['Type'], 0, 5) == 'float' || substr($k['Type'], 0, 6) == 'double' || substr($k['Type'], 0, 4) == 'real' || substr($k['Type'], 0, 7) == 'decimal') {
                $TableArray[$k['Field']]['type'] = 'float';
            } elseif (substr($k['Type'], 0, 4) == 'date') {
                $TableArray[$k['Field']]['type'] = 'date';
            } elseif (substr($k['Type'], 0, 4) == 'text' || $k['Type'] == 'mediumtext' || $k['Type'] == 'tinytext' || $k['Type'] == 'longtext') {
                $TableArray[$k['Field']]['type'] = 'text';
            } else {
                $TableArray[$k['Field']]['type'] = 'string';
            }

            $size = preg_match("/[^\(]*\((.*)\)[^\)]*/", $k['Type'], $matches, PREG_OFFSET_CAPTURE);
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
}
