<?php

namespace Bera\Db;

use Bera\Db\Exceptions\DbErrorException;
use mysqli_driver;
use mysqli_sql_exception;

/**
 * Db class
 * 
 * @author Joy Kumar Bera <joykumarbera@gmail.com>
 */
class Db {
    /**
     * @param string $db_name
     */
    private $db_name;

    /**
     * @param string $db_host
     */
    private $db_host;

    /**
     * @param string $db_user
     */
    private $db_user;

    /**
     * @param string $db_password
     */
    private $db_password;

    /**
     * @param bool $debug
     */
    private $debug;

    /**
     * @param int $port
     */
    private $port;

    /**
     * @param mysqli $con
     */
    private $con;

    /**
     * @param mysqli_stmt $stmt
     */
    private $stmt;

    /**
     * @param string $sql
     */
    private $sql;

    /**
     * @param mysqli_query $query_result
     */
    private $query_result;

    /**
     * @param mysqli_driver $mysqli_driver
     */
    private $mysqli_driver;

    /**
     * Constructor
     * 
     * @param string $dbname
     * @param string $hostname
     * @param string $user
     * @param string $password
     */
    public function __construct(string $db_name = '', string $db_host = 'localhost', string $db_user = 'root', string $db_password = '', $port = null, bool $debug = false)
    {
        $this->db_name = $db_name;
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->port = $port;

        $this->setDebugMode($debug);
        $this->checkMysqliExtensionEnabledOrNot();
        $this->initConnecton();
    }

    public function setDebugMode($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Check if mysqli extension is enabled or not
     * 
     * @throws \RuntimeException
     */
    private function checkMysqliExtensionEnabledOrNot() 
    {
        if( !extension_loaded('mysqli') ) {
            throw new \RuntimeException("mysqli extension is not enabled");
        }
    }

    /**
     * Initilize a database connection
     * 
     * @throws DbErrorException
     */
    private function initConnecton()
    {   
        $this->mysqli_driver = new mysqli_driver();

        if($this->debug) {
            $this->mysqli_driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
        } else{
            $this->mysqli_driver->report_mode = MYSQLI_REPORT_OFF;
        }
        
        $this->con = new \mysqli(
            $this->db_host,
            $this->db_user,
            $this->db_password,
            $this->db_name,
            $this->port
        );

        if($this->con->errno !== 0) {
            throw new DbErrorException('DB connection error :: ' . $this->con->error );
        }
    }

    /**
     * Insert a record
     * 
     * @param string $table
     * @param array $data
     * 
     * @return int|bool
     */
    public function insert($table, $data = [])
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $value_placeholder = '';
        for($i = 0; $i < count($values); $i++) {
            $value_placeholder .= '?, ';
        }
        $value_placeholder = rtrim($value_placeholder, ', ');
        $sql = "INSERT INTO $table ( `" . implode('`,`', $columns ) ."` )  VALUES( $value_placeholder )";

        if( $this->_runQuery($sql, $values) ) {
            return $this->lastInsertId();
        }

        return false;
    }

    /**
     * Get last insert id
     * 
     * @return int
     */
    public function lastInsertId()
    {
        return $this->stmt->insert_id;
    }
    
    /**
     * Update record
     * 
     * @param string $table
     * @param array $data
     * @param array $conditions
     * 
     * @return int|bool
     */
    public function update($table, $data = [], $conditions = [], $glue = 'AND')
    {
        $update_columns = '';
        if(!empty($data)) {
            foreach($data as $column => $value) {
                $update_columns .= '`' . $column . '`' . ' = ' . '?,';
            }
            $update_columns = rtrim($update_columns, ',');
        }

        if(!empty($conditions)) {
            $where_clause = '';
            foreach($conditions as $key => $value) {
                $where_clause .= '`' . $key . '`' . ' = ' . '? ' . $glue;
            }
            $where_clause = rtrim($where_clause, " $glue");
        } else {
            $where_clause = 1;
        }

        $sql = "UPDATE $table SET $update_columns WHERE $where_clause";
        $final_params = array_merge( array_values($data), array_values($conditions));

        if( $this->_runQuery($sql, $final_params) ) {
            return $this->getAffectedRows();
        }

        return false;
    }

    /**
     * Delete record
     * 
     * @param string $table
     * @param array $conditions
     * 
     * @return int|bool
     */
    public function delete($table, $conditions=[], $glue = 'AND')
    {
        if(!empty($conditions)) {
            $where_clause = '';
            foreach($conditions as $key => $value) {
                $where_clause .= '`' . $key . '`' . ' = ' . '? ' . $glue;
            }

            $where_clause = rtrim($where_clause, " $glue");
        } else {
            $where_clause = 1;
        }

        $sql = "DELETE FROM $table WHERE $where_clause";

        if( $this->_runQuery($sql, array_values($conditions)) ) {
            return $this->getAffectedRows();
        }

        return false;
    }

    /**
     * Delete data using AND as a glue
     * 
     * @param string $table
     * @param array $conditions
     */
    public function deleteUsingAnd($table, $conditions=[]) 
    {
        $this->delete($table, $conditions, 'AND');
    }

    /**
     * Delete data using OR as a glue
     * 
     * @param string $table
     * @param array $conditions
     */
    public function deleteUsingOr($table, $conditions=[]) 
    {
        $this->delete($table, $conditions, 'OR');
    }

    /**
     * Run a query
     * 
     * @param string $sql
     * @param array $params
     * 
     * @return Db
     * 
     * @throws DbErrorException
     */
    public function query($sql, $params = [])
    {
        $this->sql = $sql;
        if($this->_runQuery($this->sql, $params)) {
            return $this;
        } else {
            throw new DbErrorException("DB error :: " . $this->stmt->error);
        }
    }
    
    /**
     * Run the actual query
     * 
     * @param string $sql
     * @param array $params
     * 
     * @return bool
     * @throws DbErrorException
     */
    private function _runQuery($sql, $params)
    {
        try {
            $this->stmt = $this->con->prepare($sql);
        } catch(mysqli_sql_exception $e) {
            throw new DbErrorException('DB error :: ' . $this->con->error );
        }
       
        if( !empty($params) ) {
            $type_str = '';
            foreach($params as $value) {
                if( is_int($value) ) {
                    $type_str .= 'i';
                } else if(is_double($value)) {
                    $type_str .= 'd';
                } else if(is_string($value)) {
                    $type_str .= 's';
                } else {
                    $type_str .= 'b';
                }
            }

            $this->stmt->bind_param($type_str, ...$params);
        }

        try {
            $this->stmt->execute();
            $this->query_result = $this->stmt->get_result();
            return true;
        } catch(mysqli_sql_exception $e) {
            throw new DbErrorException("DB error :: " . $e->getMessage());
        }
    }

    /**
     * Find one record from a table
     * 
     * @param string $table
     * @param array $conditions
     * @param string $glue
     * @return object|array
     */
    public function findOne($table, $conditions = [], $glue = 'AND', $as = 'object')
    {
        if(!empty($conditions)) {
            $where_clause = '';
            foreach($conditions as $key => $value) {
                $where_clause .= '`' . $key . '`' . ' = ' . '? ' . $glue;
            }
            $where_clause = rtrim($where_clause, " $glue");
        } else {
            $where_clause = 1;
        }

        $sql = "SELECT * FROM `$table` WHERE $where_clause";

        $this->_runQuery($sql, array_values($conditions));

        if($as == 'object') {
            return $this->oneAsObject();
        }

        return $this->one();
    }

    /**
     * Find all records from a table
     * 
     * @param string $table
     * @param array $conditions
     * @param string $glue
     * @return array
     */
    public function findAll($table, $conditions = [], $glue='AND')
    {
        if(!empty($conditions)) {
            $where_clause = '';
            foreach($conditions as $key => $value) {
                $where_clause .= '`' . $key . '`' . ' = ' . '? ' . $glue;
            }
            $where_clause = rtrim($where_clause, " $glue");
        } else {
            $where_clause = 1;
        }

        $sql = "SELECT * FROM `$table` WHERE $where_clause";

        $this->_runQuery($sql, array_values($conditions));

        return $this->all();
    }

    /**
     * Get total nunmber of rows affected rows
     * 
     * @return int
     */
    public function getAffectedRows() 
    {
        if( $this->stmt == null ) {
            return 0;
        }

        return $this->stmt->affected_rows;
    }

    /**
     * Get single record as an array
     * 
     * @return array
     */
    public function one() 
    {
        if( $this->query_result ) {
            return $this->query_result->fetch_assoc();
        }
    }

    /**
     * Get single record as an object
     * 
     * @return object
     */
    public function oneAsObject()
    {
        if( $this->query_result ) {
            return $this->query_result->fetch_object();
        }
    }

    /**
     * Get all records
     * 
     * @return array
     */
    public function all() 
    {
        if( $this->query_result ) {
            return $this->query_result->fetch_all(MYSQLI_ASSOC);
        }
    }

    /**
     * Begin a db transaction
     */
    public function start_transaction()
    {
        $this->con->begin_transaction();
    }
    
    /**
     * End a db transaction commit the changes and if anything
     * goes wrong then rollback the current changes
     * 
     * @throws \Bera\Db\Exceptions\DbErrorException
     */
    public function end_transaction()
    {
        try {
            $this->con->commit();
        } catch( mysqli_sql_exception $e ) {
            $this->con->rollback();
            throw new DbErrorException($e->getMessage());
        }
    }
}