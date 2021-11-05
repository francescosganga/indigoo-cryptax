<?php

namespace CrypTax\Helpers;

use Exception;

class DbHelper
{
    private static $instance;
    private $connection;

    private function __construct() {
        $this->connection = mysqli_connect(DB_HOST, DB_USER, DB_PSW, DB_NAME);

        if (!$this->connection) {
            throw new Exception('Cannot connect to database');
        }

        $this->connection->query('SET NAMES utf8');
        mb_internal_encoding('UTF-8');

        $tableExists = $this->connection->query('SELECT 1 FROM cache LIMIT 1');
        if (!$tableExists) {
            $this->connection->multi_query(file_get_contents(__DIR__ . '../../resources/dump.sql'));
            while ($this->connection->next_result());
        }
    }

    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connection;
  }
}
