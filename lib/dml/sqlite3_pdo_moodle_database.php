<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Experimental pdo database class.
 *
 * @package    core_dml
 * @copyright  2008 Andrei Bautu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/pdo_moodle_database.php');

/**
 * Experimental pdo database class
 *
 * @package    core_dml
 * @copyright  2008 Andrei Bautu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sqlite3_pdo_moodle_database extends pdo_moodle_database {
    protected $database_file_extension = '.sq3.php';
    /**
     * Detects if all needed PHP stuff installed.
     * Note: can be used before connect()
     * @return mixed true if ok, string if something
     */
    public function driver_installed() {
        if (!extension_loaded('pdo_sqlite') || !extension_loaded('pdo')){
            return get_string('sqliteextensionisnotpresentinphp', 'install');
        }
        return true;
    }

    /**
     * Returns database family type - describes SQL dialect
     * Note: can be used before connect()
     * @return string db family name (mysql, postgres, mssql, oracle, etc.)
     */
    public function get_dbfamily() {
        return 'sqlite';
    }

    /**
     * Returns more specific database driver type
     * Note: can be used before connect()
     * @return string db type mysqli, pgsql, oci, mssql, sqlsrv
     */
    protected function get_dbtype() {
        return 'sqlite3';
    }

    protected function configure_dbconnection() {
        // try to protect database file against web access;
        // this is required in case that the moodledata folder is web accessible and
        // .htaccess is not in place; requires that the database file extension is php
        $this->pdb->exec('CREATE TABLE IF NOT EXISTS "<?php die?>" (id int)');
        $this->pdb->exec('PRAGMA case_sensitive_like=ON');
        $this->pdb->exec('PRAGMA encoding="UTF-8"');
        $this->pdb->exec('PRAGMA journal_mode=WAL');
        $this->pdb->exec('PRAGMA locking_mode=NORMAL');
        $this->pdb->exec('PRAGMA synchronous=NORMAL');
    }

    /**
     * Attempt to create the database
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     *
     * @return bool success
     */
    public function create_database($dbhost, $dbuser, $dbpass, $dbname, array $dboptions=null) {
        global $CFG;

        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpass = $dbpass;
        $this->dbname = $dbname;
        $filepath = $this->get_dbfilepath();
        $dirpath = dirname($filepath);
        @mkdir($dirpath, $CFG->directorypermissions, true);
        return touch($filepath);
    }

    /**
     * Returns the driver-dependent DSN for PDO based on members stored by connect.
     * Must be called after connect (or after $dbname, $dbhost, etc. members have been set).
     * @return string driver-dependent DSN
     */
    protected function get_dsn() {
        return 'sqlite:'.$this->get_dbfilepath();
    }

    /**
     * Returns the file path for the database file, computed from dbname and/or dboptions.
     * If dboptions['file'] is set, then it is used (use :memory: for in memory database);
     * else if dboptions['path'] is set, then the file will be <dboptions path>/<dbname>.sq3.php;
     * else if dbhost is set and not localhost, then the file will be <dbhost>/<dbname>.sq3.php;
     * else the file will be <moodle data path>/<dbname>.sq3.php
     * @return string file path to the SQLite database;
     */
    public function get_dbfilepath() {
        global $CFG;
        if (!empty($this->dboptions['file'])) {
            return $this->dboptions['file'];
        }
        if ($this->dbhost && $this->dbhost != 'localhost') {
            $path = $this->dbhost;
        } else {
            $path = $CFG->dataroot;
        }
        $path = rtrim($path, '\\/').'/';
        if (!empty($this->dbuser)) {
            $path .= $this->dbuser.'_';
        }
        $path .= $this->dbname.'_'.md5($this->dbpass).$this->database_file_extension;
        return $path;
    }

    /**
     * Return tables in database WITHOUT current prefix.
     * @param bool $usecache if true, returns list of cached tables.
     * @return array of table names in lowercase and without prefix
     */
    public function get_tables($usecache=true) {
        $tables = array();

        $sql = 'SELECT name FROM sqlite_master WHERE type="table" UNION ALL SELECT name FROM sqlite_temp_master WHERE type="table" ORDER BY name';
        if ($this->debug) {
            $this->debug_query($sql);
        }
        $rstables = $this->pdb->query($sql);
        foreach ($rstables as $table) {
            $table = $table['name'];
            $table = strtolower($table);
            if ($this->prefix !== false && $this->prefix !== '') {
                if (strpos($table, $this->prefix) !== 0) {
                    continue;
                }
                $table = substr($table, strlen($this->prefix));
            }
            $tables[$table] = $table;
        }
        return $tables;
    }

    /**
     * Return table indexes - everything lowercased
     * @param string $table The table we want to get indexes from.
     * @return array of arrays
     */
    public function get_indexes($table) {
        $indexes = array();
        $sql = 'PRAGMA index_list('.$this->prefix.$table.')';
        if ($this->debug) {
            $this->debug_query($sql);
        }
        $rsindexes = $this->pdb->query($sql);
        foreach($rsindexes as $index) {
            $unique = (boolean)$index['unique'];
            $index = $index['name'];
            $sql = 'PRAGMA index_info("'.$index.'")';
            if ($this->debug) {
                $this->debug_query($sql);
            }
            $rscolumns = $this->pdb->query($sql);
            $columns = array();
            foreach($rscolumns as $row) {
                $columns[] = strtolower($row['name']);
            }
            $index = strtolower($index);
            $indexes[$index]['unique'] = $unique;
            $indexes[$index]['columns'] = $columns;
        }
        return $indexes;
    }

    /**
     * Returns detailed information about columns in table. This information is cached internally.
     *
     * @param string $table The table's name.
     * @return database_column_info[] of database_column_info objects indexed with column names
     */
    protected function fetch_columns(string $table): array {
        $structure = array();

        // get table's CREATE TABLE command (we'll need it for autoincrement fields)
        $sql = 'SELECT sql FROM sqlite_master WHERE type="table" AND tbl_name="'.$this->prefix.$table.'"'.
            ' UNION ALL'.
            ' SELECT sql FROM sqlite_temp_master WHERE type="table" AND tbl_name="'.$this->prefix.$table.'"';

        if ($this->debug) {
            $this->debug_query($sql);
        }

        $createsql = $this->pdb->query($sql)->fetch();

        if (!$createsql) {
            return array();
        }

        $createsql = $createsql['sql'];
        $sql = 'PRAGMA table_info("'. $this->prefix.$table.'")';

        if ($this->debug) {
            $this->debug_query($sql);
        }

        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->pdb->query($sql);
        $this->query_end($result);

        foreach ($result as $row) {
            $columninfo = new stdClass();
            $columninfo->name = strtolower($row['name']); // Colum names must be lowercase.
            $columninfo->not_null = (boolean)$row['notnull'];
            $columninfo->primary_key = (boolean)$row['pk'];
            $columninfo->has_default = !is_null($row['dflt_value']);
            $columninfo->default_value = $row['dflt_value'];
            $columninfo->auto_increment = false;
            $columninfo->binary = false;
            $columninfo->unsigned = null;
            $columninfo->scale = null;

            $type = explode('(', $row['type']);
            $columninfo->type = strtolower($type[0]);

            if (count($type) > 1) {
                $size = explode(',', trim($type[1], ')'));
                $columninfo->max_length = $size[0];

                if (count($size) > 1) {
                    $columninfo->scale = $size[1];
                }
            }

            // SQLite does not have a fixed set of datatypes (ie. it accepts any string as
            // datatype in the CREATE TABLE command. We try to guess which type is used here
            switch(substr($columninfo->type, 0, 3)) {
                case 'int': // int integer
                    $pattern = '/'.$columninfo->name.'\W+integer\W+primary\W+key\W+autoincrement/im';
                    if ($columninfo->primary_key && preg_match($pattern, $createsql)) {
                        $columninfo->meta_type = 'R';
                        $columninfo->auto_increment = true;
                    } else {
                        $columninfo->meta_type = 'I';
                    }
                    break;
                case 'num': // number numeric
                case 'rea': // real
                case 'dou': // double
                case 'flo': // float
                    $columninfo->meta_type = 'N';
                    break;
                case 'var': // varchar
                case 'cha': // char
                    $columninfo->meta_type = 'C';
                    break;
                case 'enu': // enums
                    $columninfo->meta_type = 'C';
                    break;
                case 'tex': // text
                case 'clo': // clob
                    $columninfo->meta_type = 'X';
                    $columninfo->max_length = -1;
                    break;
                case 'blo': // blob
                case 'non': // none
                    $columninfo->meta_type = 'B';
                    $columninfo->binary = true;
                    $columninfo->max_length = -1;
                    break;
                case 'boo': // boolean
                case 'bit': // bit
                case 'log': // logical
                    $columninfo->meta_type = 'L';
                    $columninfo->max_length = 1;
                    break;
                case 'tim': // timestamp
                    $columninfo->meta_type = 'T';
                    break;
                case 'dat': // date datetime
                    $columninfo->meta_type = 'D';
                    break;
            }

            if ($columninfo->has_default && ($columninfo->meta_type == 'X' || $columninfo->meta_type == 'C')) {
                // trim extra quotes from text default values
                $columninfo->default_value = substr($columninfo->default_value, 1, -1);
            }

            $structure[$columninfo->name] = new database_column_info($columninfo);
        }

        return $structure;
    }

    /**
     * Obtain session lock
     * @param int $rowid id of the row with session record
     * @param int $timeout max allowed time to wait for the lock in seconds
     *
     * @throws dml_sessionwait_exception if cannot get a session lock.
     *
     * @return void
     */
    public function get_session_lock($rowid, $timeout) {
        try {
            $sql = "PRAGMA busy_timeout = ".$timeout;
            $this->query_start($sql, null, SQL_QUERY_AUX);
            $result = $this->pdb->query($sql);
            $this->query_end($result);

            $sql = "BEGIN EXCLUSIVE TRANSACTION";
            $this->query_start($sql, null, SQL_QUERY_AUX);
            $result = $this->pdb->query($sql);
            $this->query_end($result);

            parent::get_session_lock($rowid, $timeout);
        } catch (Exception $exception) {
            if ($exception->getCode() === 'HY000') {
                throw new dml_sessionwait_exception();
            }
        } finally {
            $sql = "PRAGMA busy_timeout = 60000"; // PHP default value. See: https://www.php.net/manual/en/function.sqlite-busy-timeout.php.
            $this->query_start($sql, null, SQL_QUERY_AUX);
            $result = $this->pdb->query($sql);
            $this->query_end($result);
        }
    }

    /**
     * Release session lock
     * @param int $rowid id of the row with session record
     * @return void
     */
    public function release_session_lock($rowid) {
        if (!$this->used_for_db_sessions) {
            return;
        }

        parent::release_session_lock($rowid);

        $sql = "COMMIT TRANSACTION";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->pdb->query($sql);
        $this->query_end($result);
    }

    /**
     * Normalise values based in RDBMS dependencies (booleans, LOBs...)
     *
     * @param database_column_info $column column metadata corresponding with the value we are going to normalise
     * @param mixed $value value we are going to normalise
     * @return mixed the normalised value
     */
    protected function normalise_value($column, $value) {
        $this->detect_objects($value);

        if (is_bool($value)) { // Always, convert boolean to int.
            $value = (int)$value;

        } else if ($value === '') {
            if ($column->meta_type == 'I' or $column->meta_type == 'F' or $column->meta_type == 'N') {
                $value = 0; // Prevent '' problems in numeric fields.
            }
        } else if (is_float($value) and ($column->meta_type == 'C' or $column->meta_type == 'X')) {
            // Any float value being stored in varchar or text field is converted to string to avoid any implicit conversion.
            $value = "$value";
        }
        return $value;
    }

    /**
     * Returns the sql statement with clauses to append used to limit a recordset range.
     *
     * @param string $sql the SQL statement to limit.
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     *
     * @return string the SQL statement with limiting clauses
     */
    protected function get_limit_clauses($sql, $limitfrom=0, $limitnum=0) {
        if ($limitnum) {
            $sql .= ' LIMIT '.$limitnum;
        }

        if ($limitfrom) {
            if (empty($limitnum)) {
                $sql .= " LIMIT -1";
            }
            $sql .= " OFFSET $limitfrom";
        }

        return $sql;
    }

    /**
     * Delete the records from a table where all the given conditions met.
     * If conditions not specified, table is truncated.
     *
     * @param string $table the table to delete from.
     * @param array $conditions optional array $fieldname=>requestedvalue with AND in between
     *
     * @return returns success.
     */
    public function delete_records($table, array $conditions=null) {
        if (is_null($conditions)) {
            return $this->execute("DELETE FROM {{$table}}");
        }
        list($select, $params) = $this->where_clause($table, $conditions);
        return $this->delete_records_select($table, $select, $params);
    }

    /**
     * Does this driver support tool_replace?
     *
     * @since Moodle 2.6.1
     * @return bool
     */
    public function replace_all_text_supported() {
        return true;
    }

    /**
     * Returns the SQL text to be used in order to perform one bitwise XOR operation
     * between 2 integers.
     *
     * NOTE: The SQL result is a number and can not be used directly in
     *       SQL condition, please compare it to some number to get a bool!!
     *
     * @param int $int1 The first operand integer in the operation.
     * @param int $int2 The second operand integer in the operation.
     *
     * @return string The piece of SQL code to be used in your statement.
     */
    public function sql_bitxor($int1, $int2) {
        $bindvariables = false;
        foreach (array($int1, $int2) as $variable) {
            if ($variable === '?') {
                $bindvariables = true;
                break;
            }

            $variable = (string) $variable;
            if (isset($variable[0]) === true && $variable[0] === ':') {
                $bindvariables = true;
                break;
            }
        }

        if ($bindvariables === true) {
            debugging(__METHOD__.' is not compatible with bind variables for SQLite3.');
            return '( ~' . $this->sql_bitor($int1, $int2) . ')';
        }

        return '( ~' . $this->sql_bitand($int1, $int2) . ' & ' . $this->sql_bitor($int1, $int2) . ')';
    }

    /**
     * Returns true if this database driver supports bitwise XOR operation.
     * @return bool True if supported.
     */
    public function sql_bitxor_supported() {
        return false;
    }

    /**
     * Returns the SQL to be used in order to CAST one CHAR column to INTEGER.
     *
     * Be aware that the CHAR column you're trying to cast contains really
     * int values or the RDBMS will throw an error!
     *
     * @param string $fieldname The name of the field to be casted.
     * @param bool $text Specifies if the original column is one TEXT (CLOB) column (true). Defaults to false.
     * @return string The piece of SQL code to be used in your statement.
     */
    public function sql_cast_char2int($fieldname, $text=false) {
        return ' CAST(' . $fieldname . ' AS INT) ';
    }

    /**
     * Returns the SQL to be used in order to CAST one CHAR column to REAL number.
     *
     * Be aware that the CHAR column you're trying to cast contains really
     * numbers or the RDBMS will throw an error!
     *
     * @param string $fieldname The name of the field to be casted.
     * @param bool $text Specifies if the original column is one TEXT (CLOB) column (true). Defaults to false.
     * @return string The piece of SQL code to be used in your statement.
     */
    public function sql_cast_char2real($fieldname, $text=false) {
        return ' CAST(' . $fieldname . ' AS REAL) ';
    }

    /**
     * Returns the cross db correct CEIL (ceiling) expression applied to fieldname.
     * note: Most DBs use CEIL(), hence it's the default here.
     *
     * @param string $fieldname The field (or expression) we are going to ceil.
     *
     * @return string The piece of SQL code to be used in your ceiling statement.
     */
    public function sql_ceil($fieldname) {
        return ' ROUND(' . $fieldname . ' + 0.5, 0)';
    }

    /**
     * Returns the proper SQL to do CONCAT between the elements passed
     * Can take many parameters
     *
     * @param string $element
     *
     * @return string
     */
    public function sql_concat() {
        $elements = func_get_args();
        return implode('||', $elements);
    }

    /**
     * Returns the proper SQL to do CONCAT between the elements passed
     * with a given separator
     *
     * @param string $separator
     * @param array  $elements
     *
     * @return string
     */
    public function sql_concat_join($separator="' '", $elements=array()) {
        // Intersperse $elements in the array.
        // Add items to the array on the fly, walking it
        // _backwards_ splicing the elements in. The loop definition
        // should skip first and last positions.
        for ($n=count($elements)-1; $n > 0; $n--) {
            array_splice($elements, $n, 0, $separator);
        }
        return implode('||', $elements);
    }

    /**
     * Returns the SQL that allows to find intersection of two or more queries
     *
     * @since Moodle 2.8
     *
     * @param array $selects array of SQL select queries, each of them only returns fields with the names from $fields
     * @param string $fields comma-separated list of fields (used only by some DB engines)
     *
     * @return string SQL query that will return only values that are present in each of selects
     */
    public function sql_intersect($selects, $fields) {
        if (!count($selects)) {
            throw new coding_exception('sql_intersect() requires at least one element in $selects');
        }

        return implode(' INTERSECT ', $selects);
    }

    /**
     * Returns 'LIKE' part of a query.
     *
     * @param string $fieldname Usually the name of the table column.
     * @param string $param Usually the bound query parameter (?, :named).
     * @param bool $casesensitive Use case sensitive search when set to true (default).
     * @param bool $accentsensitive Use accent sensitive search when set to true (default). (not all databases support accent insensitive)
     * @param bool $notlike True means "NOT LIKE".
     * @param string $escapechar The escape char for '%' and '_'.
     * @return string The SQL code fragment.
     */
    public function sql_like($fieldname, $param, $casesensitive = true, $accentsensitive = true, $notlike = false, $escapechar = '\\') {
        if (strpos($param, '%') !== false) {
            debugging('Potential SQL injection detected, sql_like() expects bound parameters (? or :named)');
        }

        $like = $notlike ? 'NOT LIKE' : 'LIKE';

        if ($casesensitive === false) {
            return "UPPER($fieldname) $like UPPER($param) ESCAPE '$escapechar'";
        }

        if ($accentsensitive === false && defined('PHPUNIT_TEST') === false) {
            debugging('SQLite3 does not handle accent-insensitive search.');
        }

        return "$fieldname $like $param ESCAPE '$escapechar'";
    }

    /**
     * Returns the SQL for returning searching one string for the location of another.
     *
     * Note, there is no guarantee which order $needle, $haystack will be in
     * the resulting SQL so when using this method, and both arguments contain
     * placeholders, you should use named placeholders.
     *
     * @param string $needle the SQL expression that will be searched for.
     * @param string $haystack the SQL expression that will be searched in.
     *
     * @return string The required searching SQL part.
     */
    public function sql_position($needle, $haystack) {
        return 'INSTR('.$haystack.', '.$needle.')';
    }

    /**
     * Return regex positive or negative match sql
     * @param bool $positivematch
     * @param bool $casesensitive
     * @return string or empty if not supported
     */
    public function sql_regex($positivematch = true, $casesensitive = false) {
        if ($casesensitive === true && defined('PHPUNIT_TEST') === false) {
            debugging('SQLite3 does not handle case sensivitve for REGEXP.');
        }

        return $positivematch ? 'REGEXP' : 'NOT REGEXP';
    }

    /**
     * Does this driver support regex syntax when searching
     */
    public function sql_regex_supported() {
        return false;
    }
}
