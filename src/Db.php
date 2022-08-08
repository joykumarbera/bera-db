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
     * @return Db
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

        return $this->query($sql, $values);
    }
    
    /**
     * Update record
     * 
     * @param string $table
     * @param array $data
     * @param array $conditions
     * 
     * @return Db
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

        return $this->query($sql, array_merge( array_values($data), array_values($conditions)));
    }

    /**
     * Delete record
     * 
     * @param string $table
     * @param array $conditions
     * 
     * @return Db
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

        return $this->query($sql, array_values($conditions));
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
        try {
            $this->stmt = $this->con->prepare($this->sql);
            if($this->stmt === false) {
                throw new DbErrorException('DB error :: ' . $this->con->error );
            }
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

        if($this->stmt->execute()) {
            $this->query_result = $this->stmt->get_result();
            return $this;
        } else {
            throw new DbErrorException("DB error :: " . $this->stmt->error);
        }
    }

    /**
     * Get total nunmber of rows
     * 
     * @return int
     */
    public function getNumRows() 
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
     * Beginb a db transaction
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