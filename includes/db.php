<?php
// Database connection helper
function get_db()
{
    static $pdo = null;
    if ($pdo) return $pdo;

    $host = '127.0.0.1';
    $db   = 'agrisea';
    $user = 'root';
    $pass = '';
    $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_TIMEOUT => 30
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Log error instead of displaying details in production
        error_log('Database connection failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        die('Database connection failed. Please check error logs for details.');
    }
}

// Initialize database if needed
function initialize_database() {
    try {
        $pdo = get_db();
        
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() == 0) {
            // Database is empty, import schema
            $schemaFile = __DIR__ . '/../db/agrisea_merged_schema.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $pdo->exec($sql);
                return true;
            }
        }
        return true;
    } catch (Exception $e) {
        error_log('Database initialization failed: ' . $e->getMessage());
        return false;
    }
}

// Test database connection
function test_db_connection() {
    try {
        $pdo = get_db();
        $stmt = $pdo->query('SELECT 1');
        return $stmt !== false;
    } catch (Exception $e) {
        return false;
    }
}