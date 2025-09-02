<?php
namespace Mws\Core;

class Database
{
    private \mysqli $conn;

    /**
     * Establishes a database connection using environment variables.
     *
     * @return \mysqli
     */
    public function connect(): \mysqli
    {
        $host     = getenv('DB_HOST');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');
        $database = getenv('DB_NAME');

        $this->conn = new \mysqli($host, $username, $password, $database);

        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }

        // Ensure proper character set
        $this->conn->set_charset("utf8mb4");

        return $this->conn;
    }
}
