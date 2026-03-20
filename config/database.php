<?php
/**
 * SafeHaven - Database Configuration
 * PDO-based database connection.
 * Auto-detects localhost vs HelioHost via IS_LOCAL constant.
 *
 * LOCAL:      Create DB named 'safehaven' (or adjust DB_NAME below)
 *             User: root, Password: (empty)
 * HELIOHOST:  Uses the credentials provided in your hosting panel.
 */

class Database {
    private static $instance = null;
    private $connection = null;

    private $host;
    private $dbname;
    private $username;
    private $password;

    private function __construct() {
        if (IS_LOCAL) {
            $this->host     = 'localhost';
            $this->dbname   = 'safehaven';   // <-- change if your local DB has a different name
            $this->username = 'root';
            $this->password = '';
        } else {
            // HelioHost production credentials
            $this->host     = 'morty.heliohost.org';
            $this->dbname   = 'zellpetermiranda_safehaven';
            $this->username = 'zellpetermiranda_admin';
            $this->password = 'Gujunpyu123';
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $this->connection = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                if (IS_LOCAL) {
                    die("<h2>Database connection failed</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>"
                        . "<p>Please create a MySQL database named <strong>{$this->dbname}</strong> and import <code>database/database.sql</code>.</p>");
                } else {
                    die("Database connection failed. Please contact the administrator.");
                }
            }
        }
        return $this->connection;
    }

    public function closeConnection(): void {
        $this->connection = null;
    }
}
