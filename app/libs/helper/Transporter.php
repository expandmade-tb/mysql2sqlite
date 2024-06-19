<?php

namespace helper;

use Exception;
use database\DbDDL;
use database\DbMSQ;
use database\DbSQ3;
use database\DBTable;
use database\DBView;

class Source extends DBView {
    protected string $dbconnect = 'MSQ';
}

class Dest extends DBTable {
    protected string $dbconnect = 'SQ3';
}

class Transporter {
    private string $db_name='';

    function __construct(string $db_host, string $db_name, string $db_user, string $db_password) {  
        $this->db_name = $db_name;  
        DbMSQ::instance($db_host, $db_name, $db_user, $db_password);
        DbSQ3::instance(BASEPATH.'/'.Helper::env('sqlite_db', 'sqlite.db'));
    }

    public function get_Tables() : array {
        $stmt = DbMSQ::instance()->query("SHOW TABLES"); 

        if ( $stmt !== false )
            return $stmt->fetchAll();
        else
            throw new Exception("error in retrieving table names from {$this->db_name}");
    }

    public function convert_to_sqlite_type(string $type) : string {
        switch (true) {
            case 'tinyint' == substr($type, 0, 7);
            case 'smallint' == substr($type, 0, 8);
            case 'mediumint' == substr($type, 0, 9);
            case 'bigint' == substr($type, 0, 6);
            case 'int' == substr($type, 0, 3);
                return 'integer';
            case 'float' == substr($type, 0, 5);
            case 'double' == substr($type, 0, 6);
                return 'real';
            case 'varchar' == substr($type, 0, 7);
            case 'tinytext' == substr($type, 0, 8);
            case 'mediumtext' == substr($type, 0, 10);
            case 'longtext' == substr($type, 0, 8);
            case 'text' == substr($type, 0, 4);
                return 'text';
            case 'numeric' == substr($type, 0, 8);
                return 'numeric';
            case 'datetime' == substr($type, 0, 8);
                return 'datetime';
            default:
                return 'not_supported';
        }
    }

    public function get_MetaData(string $table) : array {
        $stmt = DbMSQ::instance()->query("DESCRIBE $table"); 

        if ( $stmt !== false )
            return $stmt->fetchAll();
        else
            throw new Exception("cannot retrieve metadata from table {$table}");
    }

    public function get_IndexData(string $table, bool $non_unique=false) : array {
        $nu = (int)$non_unique;
        $stmt = DbMSQ::instance()->query("SHOW INDEXES FROM $table WHERE Non_unique=$nu"); 

        if ( $stmt !== false )
            return $stmt->fetchAll();
        else
            throw new Exception("error in retrieving indexes from table {$table}");
    }

    public function add_DDLFields(DbDDL &$ddl, $table) {
        $meta_data = $this->get_MetaData($table);

        foreach ($meta_data as $key => $value) {
            $name = $value["Field"];
            $type = $value["Type"];
            $not_null = $value["Null"] == 'NO';
            $default = empty($value["Default"]) == true ? null : $value["Default"]; 
            $auto_increment = $value["Extra"] == 'auto_increment' ? true : false; 
            $unique=false; // handle indexes later
            
            switch ($this->convert_to_sqlite_type($type)) {
                case 'integer':
                    $ddl->integer($name, $not_null, $auto_increment, $unique, $default);
                    break;
                case 'real':
                    $ddl->real($name, $not_null, $default);
                    break;
                case 'text':
                    $ddl->text($name, 0, $not_null, $unique, $default);
                    break;
                case 'numeric':
                    $ddl->numeric($name, $not_null, $default);
                    break;
                case 'datetime':
                    $ddl->datetime($name, $not_null, $unique, $default);
                    break;
                default:
                    throw new Exception("unknown data type in table $table");
            }
        }
    }

    public function add_DDLConstraints(DbDDL &$ddl, $table) {
        $index_data = $this->get_IndexData($table);
        $indexes = []; // list of all unique indexes
        
        foreach ($index_data as $idx => $value) {
            $key = $value['Key_name'];
            $column = $value['Column_name'];

            if (empty($indexes[$key]))
                $indexes[$key] = $column;
            else {
               $fields = $indexes[$key];
               $indexes[$key] = "$fields,$column";
            }
        }
        
        foreach ($indexes as $key => $value) {
            $fields = $indexes[$key];
            
            if ($key == 'PRIMARY' )
                $ddl->primary_key($fields);
            else
                if ( substr_count($fields, "'") == 0)
                    $ddl->unique($fields);
                else
                    $ddl->unique_constraint($fields);
        }
    }

    public function add_DDLIndexes(DbDDL &$ddl, $table) {
        $index_data = $this->get_IndexData($table, true);
        $indexes = []; // list of non unique indexes
        
        foreach ($index_data as $idx => $value) {
            $key = $value['Key_name'];
            $column = $value['Column_name'];

            if (empty($indexes[$key]))
                $indexes[$key] = $column;
            else {
               $fields = $indexes[$key];
               $indexes[$key] = "$fields,$column";
            }
        }
        
        foreach ($indexes as $key => $value) {
            $fields = $indexes[$key];
            $ddl->index($fields);
        }
    }

    public function add_DDLForgeinKeys(DbDDL &$ddl, $table) {
        $is = new Source('information_schema.KEY_COLUMN_USAGE');
        $forgeign_key_data = $is->where('TABLE_NAME', $table)->where('REFERENCED_TABLE_SCHEMA', $this->db_name)->findAll();

        foreach ($forgeign_key_data as $key => $value) {
            $key = $value['CONSTRAINT_NAME'];
            $column = $value['COLUMN_NAME'];
            $parent_table = $value['REFERENCED_TABLE_NAME'];
            $parent_key = $value['REFERENCED_COLUMN_NAME'];
            $ddl->foreign_key($column, $parent_table, $parent_key);            
        }
    }

    public function getDDL(string $table) {
        $ddl = new DbDDL($table);
        $this->add_DDLFields($ddl, $table);
        $this->add_DDLConstraints($ddl, $table);
        $this->add_DDLForgeinKeys($ddl, $table);
        $this->add_DDLIndexes($ddl, $table);
        return $ddl->createSQ3();
    }
    
    public function transport(string $table, array $args=[]) : int {
        // get optional arguments and set their defaults
        $use_transactions=true;
        $row_chunks=100;
        $on_exception_stop=true;
        $empty_is_null=false;
        extract($args, EXTR_IF_EXISTS);

        $source = new Source($table);
        $result = $this->getDDL($table);

        try {
            $dest = new Dest($table, $this->getDDL($table));
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            Helper::writeln($msg);
            Helper::writeln($result);

            if ($on_exception_stop)
                exit;
            else
                return 0;
        }

        $rowcount = $source->count();
        $offset = 0;
        $limit = $row_chunks;
        $lines = 0;

        while ($offset < $rowcount ) {
            $rows = $source->limit($limit)->offset($offset)->findAll();

            if ($use_transactions)
                $dest->database()->beginTransaction();

            foreach ($rows as $row_no => $row) {
                $offset++;

                try {
                    $result = $dest->insert($row, $empty_is_null);
                } catch (\Throwable $th) {
                    if ($use_transactions)
                        $dest->database()->rollBack();

                        $msg = $th->getMessage();
                    Helper::writeln($msg);
                    Helper::writeln("insert row in table $table failed:");
                    print_r($row);

                    if ($on_exception_stop)
                        exit;
                }
                
                $lines++;
            }

            if ($use_transactions)
                $dest->database()->commit();
        }

        return $lines;
    }

}