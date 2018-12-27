<?php

/**
 *
 * @version 1.0
 * @author Paul
 */
class Db extends PDO
{
    private $statement;
    const DRIVER_SPECIFIC_ERROR_MESSAGE = 2;

    public function __construct() {
        require_once('classes/config.php');
        $db_host = Config::get_database_host();
        $db_username = Config::get_database_user();
        $db_name = Config::get_database_name();
        $db_password = Config::get_database_password();
        $dsn = "mysql:host=" . $db_host . ";dbname=" . $db_name;
        $MYSQL_ATTR_INIT_COMMAND = 1002;
        $mysql_params = array($MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4", PDO::ATTR_EMULATE_PREPARES=>false, PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION);
        parent::__construct($dsn, $db_username, $db_password, $mysql_params);
    }

    public function exec_sql($sql_statement, $array_of_params = array()) {
        try {
            unset($this->statement);
            if (!($this->statement = $this->prepare($sql_statement))) {
                $pdo_err = $this->statement->errorInfo();
                $err = isset($pdo_err[self::DRIVER_SPECIFIC_ERROR_MESSAGE]) ? $pdo_err[self::DRIVER_SPECIFIC_ERROR_MESSAGE] : "Unknown error since pdo_err[self::DRIVER_SPECIFIC_ERROR_MESSAGE] isn't set.";
                throw new Exception("Error in exec_sql Unable to prepare the query. " . $err . "<br>SQL=" . $sql_statement);
            }
            if (!$this->statement->execute($array_of_params)) {
                $pdo_err = $this->statement->errorInfo();
                $err = isset($pdo_err[self::DRIVER_SPECIFIC_ERROR_MESSAGE]) ? $pdo_err[self::DRIVER_SPECIFIC_ERROR_MESSAGE] : "Unknown error since pdo_err[self::DRIVER_SPECIFIC_ERROR_MESSAGE] isn't set.";
                throw new Exception($err);
            }
            return true;
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function fetch_rows($sql_statement, $array_of_params = array(), $fetch_style = PDO::FETCH_BOTH) {
        try {
            switch ($fetch_style) {
                case "assoc":
                case PDO::FETCH_ASSOC:
                    $method = PDO::FETCH_ASSOC;
                    break;
                case "num":
                case "array":
                case PDO::FETCH_NUM:
                    $method = PDO::FETCH_NUM;
                    break;
                case "both":
                case PDO::FETCH_BOTH:
                    $method = PDO::FETCH_BOTH;
                    break;
                default:
                    $method = PDO::FETCH_BOTH;
            }

            $this->exec_sql($sql_statement, $array_of_params, PDO::FETCH_ASSOC);
            $rows = $this->statement->fetchAll($method);
            return $rows;
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_meta_data() {
        $meta = array();
        foreach(range(0, $this->statement->columnCount() - 1) as $column_index)
        {
            $column_meta = $this->statement->getColumnMeta($column_index);
            $column_meta['len'] = isset($column_meta['len']) ? $column_meta['len'] : 0;
            $field_name = $column_meta["name"];
            $column_meta["simple_type"] = $this->simplify_type($column_meta["native_type"], $column_meta["precision"]);
            $meta[$field_name] = $column_meta;
        }
        return $meta;
    }

    private function simplify_type($native_type, $precision) {
        $simple_type = $native_type;
        if (stristr($native_type, "string")) {
            $simple_type = "string";
        } elseif (stristr($native_type, "long") || stristr($native_type, "int") || stristr($native_type, "short")) {
            $simple_type = "integer";
        } elseif (stristr($native_type, "float") || stristr($native_type, "double")) {
            $simple_type = "float";
        } else if (stristr($native_type, "decimal")) {
            if ($precision == 2) {
                $simple_type = "money";
            } else {
                $simple_type = "float";
            }
        } else if (stristr($native_type, "bool")) {
            $simple_type = "boolean";
        } else if (stristr($native_type, "blob")) {
            $simple_type = "text";
        }
        return $simple_type;
    }
}