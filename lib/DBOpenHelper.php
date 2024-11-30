<?php

class DBOpenHelper
{
    private $pdo;
    private $tableFields = [
        "weeks" => ["year", "number", "title", "verse_reference", "verse_text"],
        "week_days" => ["day_index", "reference", "weeks_id"],
        "users" => ["name", "password", "missionary"]
    ];
    private $cmdInsert = "insert into :table (:fields) values (:values)";
    private $cmdUpdate = "update :table set :sets where id = :id";
    
    function __construct() {
        $dsn = "mysql:dbname=u216788056_pvmzapp;host=sql356.main-hosting.eu";
        $user = "u216788056_admin";
        $pass = "dbpvmz";
        $this->pdo = new PDO($dsn, $user, $pass);
    }

    function queryAll($table, $orderBy = null) {
        try {
            return $this->pdo->query("select * from $table " . ($orderBy ?? ""))->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $th) {
            throw new Exception("Erro no processo de busca do banco de dados", 1);
        }
    }

    function queryWhere($table, $where) {
        try {
            // $where = array();
            // $columns = array_keys($params);
            // foreach ($columns as $column) {
            //     array_push($where, "$column = :$column");
            // }
            // $cmd = "select * from $table where " . implode(" and ", $where);
            
            // $stm = $this->pdo->prepare($cmd);
            // foreach ($columns as $column) {
            //     $stm->bindValue(":" . $column, $params[$column]);
            // }

            // return $stm->execute()->fetchAll(PDO::FETCH_ASSOC);

            return $this->pdo->query("select * from $table where $where")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $th) {
            throw new Exception("Erro no processo de busca do banco de dados", 1);
        }
    }

    function execInsert($table, $params) {
        try {
            $fields = $values = '';
            foreach ($this->tableFields[$table] as $key) {
                $fields = (empty($fields) ? $fields : "$fields,") . "$key";
                $values = (empty($values) ? $values : "$values,") . "'$params[$key]'";
            }
            
            $cmd = "insert into $table ($fields) values ($values)";
            $status = $this->pdo->prepare($cmd)->execute();
            $result = $status ? $this->pdo->lastInsertId() : $status;

            return $result;
        } catch (\Throwable $th) {
            throw new Exception("Erro no processo de inserção do banco de dados", 1);
        }
    }
    
    function execUpdate($table, $params) {
        try {
            $sets = '';
            foreach ($this->tableFields[$table] as $key) {
                if (!empty($params[$key])) {
                    $sets = (empty($sets) ? $sets : "$sets,") . "$key = '$params[$key]'";
                }
            }

            $cmd = "update $table set $sets where id = " . $params["id"];
            $status = $this->pdo->prepare($cmd)->execute();

            return $status;
        } catch (\Throwable $th) {
            throw new Exception("Erro no processo de atualização do banco de dados", 1);
        }
    }

    function execDelete($table, $where) {
        try {
            $cmd = "delete from $table where $where";
            $status = $this->pdo->prepare($cmd)->execute();
            return $status;
        } catch (\Throwable $th) {
            throw new Exception("Erro no processo de deleção do banco de dados", 1);
        }
    }
}