<?php

namespace Bera\Db;

use Bera\Db\Exceptions\DbErrorException;
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
     * Constructor
     * 
     * @param string $dbname
     * @param string $hostname
     * @param string $user
     * @param string $password
     */
    public function __construct($db_name = '', $db_host = 'localhost', $db_user = 'root', $db_password = '')
    {
        $this->db_name = $db_name;
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_password = $db_password;

        $this->checkMysqliExtensionEnabledOrNot();
        $this->initConnecton();
    }

    /**
     * Check if mysqli extension is enabled or not
     * 
     * @throws \Exception
     */
    private function checkMysqliExtensionEnabledOrNot() 
    {
        if( !extension_loaded('mysqli') ) {
            throw new \Exception("mysqli extension is not enabled");
        }
    }

    /**
     * Initilize a database connection
     * 
     * @throws DbErrorException
     */
    private function initConnecton()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->con = new \mysqli(
            $this->db_host,
            $this->db_user,
            $this->db_password,
            $this->db_name
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
     * @return DB
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
     * @return DB
     */
    public function update($table, $data = [], $conditions = [])
    {
        $updates = '';
        if(!empty($data)) {
            foreach($data as $column => $value) {
                $updates .= '`' . $column . '`' . ' = ' . '?,';
            }
            $updates = rtrim($updates, ',');
        }

        if(!empty($conditions)) {
            $where_clause = '';
            foreach($conditions as $key => $value) {
                $where_clause .= '`' . $key . '`' . ' = ' . '?,';
            }
            $where_clause = rtrim($where_clause, ',');
        } else {
            $where_clause = '1';
        }

        $sql = "UPDATE $table SET $updates WHERE $where_clause";

        return $this->query($sql, array_merge( array_values($data), array_values($conditions)));
    }

    /**
     * Delete record
     * 
     * @param string $table
     * @param array $conditions
     * 
     * @return DB
     */
    public function delete($table, $conditions=[])
    {
        if(!empty($conditions)) {
            $where_clause = '';
            foreach($conditions as $column => $value) {
                $where_clause .= '`' . $column . '`' . ' = ' . '?,';
            }
            $where_clause = rtrim($where_clause, ',');
        } else {
            $where_clause = '1';
        }

        $sql = "DELETE FROM $table WHERE $where_clause";

        return $this->query($sql, array_values($conditions));
    }

    /**
     * Run a query
     * 
     * @param string $sql
     * @param array $params
     * @return DB
     * 
     * @throws Exception
     */
    public function query($sql, $params = [])
    {
        $this->sql = $sql;
        $this->stmt = $this->con->prepare($this->sql);

        if($this->stmt === false) {
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
        return $this->stmt->affected_rows;
    }

    /**
     * Get one record as array
     * 
     * @return array
     */
    public function one() 
    {
        return $this->query_result->fetch_assoc();
    }

    /**
     * Get one record as object
     * 
     * @return object
     */
    public function oneAsObject()
    {
        return $this->query_result->fetch_object();
    }

    /**
     * Get all records
     * 
     * @return array
     */
    public function all() 
    {
        return $this->query_result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Start a db transaction
     */
    public function start_transaction()
    {
        $this->con->begin_transaction();
    }
    
    /**
     * End a db transaction commit the changes and if anything
     * goes wrong then rollback the current changes
     */
    public function end_transaction()
    {
        try {
            $this->con->commit();
        } catch( mysqli_sql_exception $e ) {
            $this->con->rollback();

            throw $e;
        }
    }
}