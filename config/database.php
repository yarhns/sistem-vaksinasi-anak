<?php
/**
 * Database connection — singleton pattern.
 * Credential dibaca dari config/database.local.php (tidak di-commit).
 */

class Database
{
    private static ?Database $instance = null;
    private mysqli $conn;

    private function __construct()
    {
        $configPath = __DIR__ . '/database.local.php';
        if (!file_exists($configPath)) {
            error_log('Database config not found: ' . $configPath);
            throw new RuntimeException(
                'Konfigurasi database tidak ditemukan. Copy config/database.example.php menjadi config/database.local.php.'
            );
        }

        $cfg = require $configPath;

        // mysqli modern: lempar exception, bukan return false
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->conn = new mysqli(
                $cfg['host'],
                $cfg['username'],
                $cfg['password'],
                $cfg['database'],
                $cfg['port'] ?? 3306
            );
            $this->conn->set_charset($cfg['charset'] ?? 'utf8mb4');
        } catch (mysqli_sql_exception $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Koneksi database gagal. Silakan hubungi administrator.');
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli
    {
        return $this->conn;
    }

    public function query(string $sql)
    {
        return $this->conn->query($sql);
    }

    public function prepare(string $sql): mysqli_stmt
    {
        return $this->conn->prepare($sql);
    }

    public function escape(string $value): string
    {
        return $this->conn->real_escape_string($value);
    }

    public function getLastInsertId(): int
    {
        return $this->conn->insert_id;
    }

    public function close(): bool
    {
        return $this->conn->close();
    }

    private function __clone() {}
    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton');
    }
}

// Instance global — kompatibel dengan kode lama yang pakai $db
global $db;
$db = Database::getInstance();