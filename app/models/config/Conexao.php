<?php

namespace Name\Models\Config;

use Exception;
use PDO;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '../../../..');
$dotenv->load();

class Conexao
{
    private static $instance;

    private function __construct() {}

    public static function getConexao()
    {
        if (!isset(self::$instance)) {
            $dbname = $_ENV['DB_NAME'];
            $host = $_ENV['DB_HOST'];
            $user = $_ENV['DB_USER'];
            $pass = $_ENV['DB_PASS'];

            try {
                self::$instance = new PDO('mysql:dbname=' . $dbname . ';host=' . $host, $user, $pass);
            } catch (Exception $e) {
                echo "Erro: " . $e;
            }
        }
        return self::$instance;
    }
}
