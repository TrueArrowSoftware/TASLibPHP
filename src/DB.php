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
    public $Charset = 'utf8mb4';


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
    /**
     * @param string $server
     * @param string $user
     * @param string $password
     * @param string $DBname
     */
    public function __construct($server = 'localhost', $user = 'root', #[\SensitiveParameter] $password = '', $DBname = 'demo')
    {
        $this->Server = $server;
        $this->User = $user;
        $this->Password = $password;
        $this->DBName = $DBname;
        $this->MySqlObject = null;
        $this->MysqlVersion = 5;
    }

    /**
     * print_r/var_dump of the connection (or of anything holding it) never show the password.
     */
    public function __debugInfo(): array
    {
        return array_merge(get_object_vars($this), ['Password' => '********']);
    }

    /**
     * Public Function to connect to db.
     */
    public function Connect()
    {
        $this->CleanError();
        $this->MySqlObject = new \mysqli($this->Server, $this->User, $this->Password, $this->DBName);
        if ($this->MySqlObject->connect_errno) {
            $this->SetError('Connect Error (' . $this->MySqlObject->connect_errno . ') ' . $this->MySqlObject->connect_error);
            $this->_isconnected = false;

            throw new \Exception('Unable to connect to database');
        }
        $this->_isconnected = true;

        $this->MySqlObject->set_charset($this->Charset);
        $this->MySqlObject->query("SET collation_connection = '".$this->Collation."'");
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

    /**
     * @param string $str
     * @return string
     */
    public function Escape($str)
    {
        return $this->MySqlObject->real_escape_string($str);
    }

    /**
     * Quote a table or column name for SQL, "table" or "db.table". Whatever a part contains stays part of the name,
     * so a name that came from outside can't end the identifier. Values still go in as placeholders, not here.
     *
     * @throws \InvalidArgumentException for an empty name or part, or a NUL byte
     */
    public static function QuoteIdentifier(string $name): string
    {
        $parts = explode('.', $name);
        if (in_array('', $parts, true) || str_contains($name, "\0")) {
            throw new \InvalidArgumentException('Invalid SQL identifier');
        }

        return '`' . implode('`.`', str_replace('`', '``', $parts)) . '`';
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
    /**
     * Main function to execute any sql query. In case you use Store procedure use ClearStoredResults to clear result set.
     *
     * @param string $query SQL query to execute
     * @param array $values Optional array of values for prepared statement
     * @return mixed MySQL result set on success, false on failure
     */
    public function Execute($query, ?array $values = null)
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

        try {
            // If values are provided, use prepared statement
            if (is_array($values) && !empty($values)) {
                $stmt = $this->MySqlObject->prepare($query);

                if (is_bool($stmt) && false === $stmt) {
                    $this->SetError("<br />Error preparing query: " . $this->MySqlObject->error);
                    return false;
                }

                // Determine types string for bind_param
                $types = '';
                foreach ($values as $val) {
                    if (is_int($val)) {
                        $types .= 'i';
                    } elseif (is_float($val)) {
                        $types .= 'd';
                    } elseif (is_bool($val)) {
                        $types .= 'i';
                    } else {
                        $types .= 's';
                    }
                }

                // Create references array for bind_param
                $bindParams = array($types);
                foreach ($values as $key => $val) {
                    $bindParams[] = &$values[$key];
                }

                // Call bind_param with the dynamically built parameter array
                call_user_func_array(array($stmt, 'bind_param'), $bindParams);

                $stmt->execute();
                $result = $stmt->get_result();

                if (false === $result) {
                    if ($stmt->errno) {
                        $this->SetError("<br />Error in Query {$query} is " . $stmt->error);
                        return false;
                    }
                    $result = true; // INSERT/UPDATE/DELETE have no result set, same as query() gives
                }
                $stmt->close();
            } else {
                // Original direct query execution
                $result = $this->MySqlObject->query($query, MYSQLI_STORE_RESULT);
            }
        } catch (\Exception $ex) {
            $this->SetError('<br />Exception In Query ' . $ex->getMessage());
            $result = false;
        }

        if (false === $result) {
            $this->SetError("<br />Error in Query {$query} is " . $this->MySqlObject->error . '::' . print_r($result, true));

            return false;
        }
        // $this->MySqlObject->store_result();

        return $result;
    }

    /**
     * Execute Query and return first row, first column if success else it returns false.
     *
     * @param string $query
     * @return mixed
     */
    public function ExecuteScalar($query, ?array $values = null)
    {
        $result = $this->Execute($query, $values);
        if ($result) {
            $this->CleanError();
            if ($result->num_rows > 0) {
                $rs = @$result->fetch_row() or $this->SetError("<br />Error in Query {$query} is " . $this->MySqlObject->error);

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
     * @param string $query
     * @return array|bool
     */
    public function ExecuteScalarRow($query, ?array $values = null)
    {
        $result = $this->Execute($query, $values);
        if ($result) {
            $this->CleanError();
            if (!is_bool($result) && $result->num_rows > 0) {
                $rs = $this->FetchArray($result);

                return ($rs) ? $rs : false;
            }
        }

        $this->SetError('No Record found on your query : ' . $query);

        return false;
    }

    /**
     * Shorthand function to comvert a recordset to array.
     */
    public function ExecuteAll(string $query, ?array $values = null): array
    {
        $result = $this->Execute($query, $values);

        return (false !== $result && static::Count($result) > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * @deprecated 2.0.0 use static DB:Count instead
     *
     *  Return number of rows in give recordset. Use Static function DB::Count instead for shorter syntax.
     *
     * @param \mysqli_result|bool $result
     * @return int
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
     * @param mixed $result
     * @return int
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
     * @param \mysqli_result $rs
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
            $this->SetError($this->MySqlObject->error . " \r\n" . $ex->getMessage());

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
            $this->SetError($this->MySqlObject->error . " \r\n" . $ex->getMessage());

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
            $this->SetError($this->MySqlObject->error . " \r\n" . $ex->getMessage());

            return false;
        }

        return (is_array($row)) ? $row : false;
    }

    /**
     * function to insert record by taking array in following form
     * $value[index] = value : where $value is array name to parse,
     * index = db column name, and value is value to insert.
     *
     * @param string $tablename
     * @param array $values
     * @param string $datatype
     * @return bool
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

        $query = 'INSERT INTO ' . self::QuoteIdentifier($tablename) . ' (' . implode(',', array_map([self::class, 'QuoteIdentifier'], $keys)) . ') VALUES (' . str_repeat('?,', count($keys) - 1) . '?)';
        if ($this->Debug) {
            echo "\n<br>Insert Query is : " . $query;
        }
        $stmt = $this->MySqlObject->prepare($query);
        if (is_bool($stmt) && false === $stmt) {
            $this->SetError('Query preparation fails possible mismatch columns (Error thrown: ' . $this->MySqlObject->error . ')');

            Log::AddEvent([
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
        Log::AddEvent([
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
     * index = db column name, and value is value to update.
     *
     * @param string $tablename
     * @param array $values
     * @param mixed $editid
     * @param string $editfield
     * @param string $datatype
     * @return bool
     */
    public function Update($tablename, $values, $editid, $editfield, $datatype = '')
    {
        return self::Update2($tablename, $values, [$editfield => $editid], $datatype);
    }

    /**
     * Runs the Update query on given data on given condition.
     */
    public function Update2(string $tablename, array $values, array $editcondition, string $datatype = '')
    {
        $query = '';
        $refs = [];
        if (empty($tablename)) {
            return false;
        }
        if (!is_array($values)) {
            return false;
        }
        if (0 == count($editcondition)) {
            return false;
        }

        $keys = array_keys($values);

        $columnlist = [];
        foreach (array_keys($values) as $k) {
            $refs[] = &$values[$k];
            $columnlist[] = self::QuoteIdentifier($k) . '=?';
        }

        $datatype = empty($datatype) ? static::GetDataString($tablename, $values) : $datatype;

        $wherecondition = [];
        foreach ($editcondition as $k => $v) {
            $refs[] = &$editcondition[$k];
            $datatype .= is_numeric($v) ? 'i' : 's';
            $wherecondition[] = ' ' . self::QuoteIdentifier($k) . ' = ?';
        }
        $query = 'Update ' . self::QuoteIdentifier($tablename) . ' set ' . implode(', ', $columnlist) . ' where ' . implode(' and ', $wherecondition);

        if ($this->Debug) {
            echo "\n<br>Update Query is : " . $query . "\r\n<br \\>" . print_r($refs, true);
        }

        $stmt = $this->MySqlObject->prepare($query);
        if (is_bool($stmt) && false === $stmt) {
            $this->SetError('Query preparation fails possible mismatch columns (Error thrown: ' . $this->MySqlObject->error . ')');
            Log::AddEvent([
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
        Log::AddEvent([
            'message' => 'Database Update Failed !!!',
            'query' => $query,
            'error' => $this->MySqlObject->error,
        ], 'normal');

        $this->SetError($this->MySqlObject->error);
        $this->CleanError();

        return false;
    }

    /**
     * @param string $table
     * @param array $values
     * @param string $datatype
     * @return bool
     */
    public function InsertUpdate($table, $values, $datatype = '')
    {
        if ('' != $table) {
            if (is_array($values)) {
                $keys = array_keys($values);

                $datatype = empty($datatype) ? static::GetDataString($table, $values) : $datatype;
                if (strlen($datatype) != count($keys)) {
                    $this->SetError('Error in Preparing Query not all column founds');
                    $this->CleanError();

                    return false;
                }

                $query = 'INSERT INTO ' . self::QuoteIdentifier($table) . ' (' . implode(',', array_map([self::class, 'QuoteIdentifier'], $keys)) . ') VALUES (' . str_repeat('?,', count($keys) - 1) . '?)
				            ON DUPLICATE KEY UPDATE ';

                $refs = [];
                $ref2 = [];
                $queryAddon = [];
                $datatype .= $datatype;
                $refs[] = &$datatype;
                foreach ($values as $key => $value) {
                    $queryAddon[] = self::QuoteIdentifier($key) . '=?';
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
            }
        }
        return false;
    }

    /**
     * function to REPLACE record by taking array in following form
     * $value[index] = value : where $value is array name to parse,
     * index = db column name, and value is value to REPLACE
     * @param string $tablename
     * @param array $values
     * @return bool
     */
    public function ReplaceArrayById($tablename, $values)
    {
        if ('' != $tablename) {
            if (is_array($values)) {
                $sets = [];
                foreach (array_keys($values) as $index) {
                    $sets[] = self::QuoteIdentifier($index) . ' = ?';
                }
                $query = 'REPLACE into ' . self::QuoteIdentifier($tablename) . ' set ' . implode(', ', $sets);
                if ($this->Debug) {
                    echo "\n<br>REPLACE Query is : " . $query;
                }
                $this->CleanError();
                $output = $this->Execute($query, array_values($values));
                $output = (true === $output) ? true : false;
                if (!$output) {
                    $this->SetError('Error in Query :' . $query . ' with error  ' . $this->MySqlObject->error);
                    $this->CleanError();
                }

                return $output;
            }
        }
        return false;
    }

    /**
     * Insert in Bulk Query. Values go in as placeholders, so any value is stored as is.
     * The columns are the keys of the first row, the other rows are matched to them by key (a missing key is NULL).
     *
     * @param string $tablename name of the table to insert into
     * @param array  $values Array of data in array( array("columnname1"=> "data", "column2"=>"Data" ) )
     * @param bool   $failonError not in use as of now.
     * @return bool
     */
    public function InsertBulk($tablename, $values, $failonError = false)
    {
        $this->CleanError();
        if (!is_array($values)) {
            $this->SetError('Incorrect Argument value for InsertBulk');

            return false;
        }

        return $this->BulkWrite('INSERT', $tablename, $values);
    }

    /**
     * Multi-row INSERT or REPLACE on placeholders. The columns are the keys of the first row.
     * Rows are sent in groups that stay under MySQL's 65535 placeholders per statement.
     * ponytail: the groups are separate statements, wrap the call in a transaction when it must be all or nothing.
     *
     * @param string $command 'INSERT' or 'REPLACE'
     * @param string $tablename
     * @param array<int, array<string, mixed>> $rows
     * @return bool
     */
    private function BulkWrite(string $command, $tablename, array $rows): bool
    {
        $rows = array_values($rows);
        if (0 == count($rows) || !is_array($rows[0]) || 0 == count($rows[0])) {
            $this->SetError('No rows to write');

            return false;
        }

        $columns = array_keys($rows[0]);
        $head = $command . ' INTO ' . self::QuoteIdentifier($tablename) . ' (' . implode(',', array_map([self::class, 'QuoteIdentifier'], $columns)) . ') VALUES ';
        $oneRow = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';

        foreach (array_chunk($rows, max(1, intdiv(60000, count($columns)))) as $chunk) {
            $params = [];
            foreach ($chunk as $row) {
                foreach ($columns as $column) {
                    $params[] = $row[$column] ?? null;
                }
            }
            $query = $head . implode(',', array_fill(0, count($chunk), $oneRow));
            if ($this->Debug) {
                echo "\n<br>{$command} Query is : " . $query;
            }
            if (!$this->Execute($query, $params)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Insert multiple array.
     *
     * @param string $tablename
     * @param array $values
     * @return bool
     */
    public function InsertMultiArray($tablename, $values = [])
    {
        $this->CleanError();
        if (is_array($values)) {
            if (isset($values['data']) && is_array($values['data'])) {
                return $this->BulkWrite('INSERT', $tablename, $this->MergeCommon($values));
            }

            return true;
        }

        return false;
    }

    /**
     * Every row of $values['data'] with the $values['common'] columns added.
     *
     * @return array<int, array<string, mixed>>
     */
    private function MergeCommon(array $values): array
    {
        $rows = [];
        foreach ($values['data'] as $row) {
            if (is_array($row)) {
                $rows[] = array_merge($row, $values['common'] ?? []);
            }
        }

        return $rows;
    }

    /**
     * Replace multiple array.
     *
     * @param string $tablename
     * @param array $values
     * @return bool
     */
    public function ReplaceMultiArrayByID($tablename, $values = [])
    {
        $this->CleanError();
        if (is_array($values)) {
            if (isset($values['data']) && is_array($values['data'])) {
                return $this->BulkWrite('REPLACE', $tablename, $this->MergeCommon($values));
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the database Record set with given condition.
     * $orderby and $where are SQL as is, never pass request data in them.
     *
     * @param string $table
     * @param string $orderby
     * @param string $where
     * @return mixed
     */
    public function DBRecordSet($table, $orderby = '', $where = 'status = 1')
    {
        $query = 'Select * from ' . $GLOBALS['Tables'][$table];
        if ('' != trim($where)) {
            $query .= ' where ' . $where;
        }
        if ('' != trim($orderby)) {
            $query .= ' order by ' . $orderby;
        }
        if ($this->Debug) {
            echo 'Query is' . $query;
        }

        return $this->Execute($query);
    }

    /**
     * Delete from given table on given ID.
     *
     * @param string $table
     * @param int $id
     * @param string $idfield
     * @return bool|mixed
     */
    public function Delete($table, $id, $idfield)
    {
        if (!is_numeric($id) || $id < 0) {
            return false;
        }
        $id = (int) $id;

        return $this->Execute('Delete from ' . self::QuoteIdentifier($table) . ' Where ' . self::QuoteIdentifier($idfield) . " = {$id}");
    }

    /**
     * @param mixed $result
     * @return array
     */
    public static function Columns($result)
    {
        $fields = [];
        if ($result && DB::Count($result) >= 0) {
            $fields = @$result->fetch_fields();
        }

        return $fields;
    }

    /**
     * Get all Columns of given table in form of array.
     *
     * @param string $table Either DB Based Table name or Index of GLOBAL TABLE
     * @return array
     */
    public static function GetColumns($table)
    {
        if (isset($GLOBALS['Tables'][$table])) { // We can get either the DB base Table name or our Table Array index.
            $table = $GLOBALS['Tables'][$table];
        }
        $f = $GLOBALS['db']->Execute('SHOW Full Columns from ' . self::QuoteIdentifier($table));
        if (null == $f) {
            throw new \Exception('Table ' . $table . ' not found');
        }
        $fields = [];
        foreach ($f as $row) {
            $fields[] = $row;
        }

        return $fields;
    }

    /**
     * @param string $query
     * @return array
     */
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

    /**
     * @param string $tablename
     * @return array
     */
    public static function GetTableInformation($tablename)
    {
        $x = DB::GetColumns($tablename);

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
            $TableArray[$k['Field']]['Key'] = $k['Key'];
            $TableArray[$k['Field']]['Comment'] = $k['Comment'];
        }

        return $TableArray;
    }

    /**
     * @param string $tablename
     * @return mixed
     */
    public static function GetAutoIncrementID($tablename)
    {
        if ($GLOBALS['db']->MysqlVersion >= 8) {
            $GLOBALS['db']->Execute('SET information_schema_stats_expiry = 0; ');
        }
        $result = $GLOBALS['db']->ExecuteScalarRow("show table status where Name = '" . $GLOBALS['db']->Escape($tablename) . "'");

        return $result['Auto_increment'];
    }

    /**
     * @param \mysqli_result $recordset
     * @return false|string
     */
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
            Log::AddEvent([
                'message' => 'Database Query Fail in ' . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'],
                'error' => $error,
            ], 'normal');
        }

        $this->CleanError();
        $this->SQLERROR = $error;
    }

    /**
     * Data Type of given column.
     * @param string $tablename
     * @param array $values
     * @return string
     */
    private function GetDataString(string $tablename, array $values)
    {
        $datatype = '';
        $Columns = DB::GetColumns($tablename);
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
