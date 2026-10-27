<?php
/**
 * Database.php
 * Handles the single PDO connection used throughout the system.
 */

class Database
{
    private string $host = "sql102.infinityfree.com";
    private string $dbName = "if0_43285928_labasset";
    private string $username = "if0_43285928";
    private string $password = "0bKv66s7Lyaxk";
    private ?PDO $conn = null;

    public function connect(): PDO
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            // Always throw exceptions on error so calling code can catch them
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Never leak credentials/DSN details to the end user
            die("Database connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}