<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Singleton Database Manager wrapping PDO with prepared statements,
 * transaction helpers, and credential-safe error logging.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Get or initialize the PDO instance
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = App::getInstance()->getConfig('database');

            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config['driver'] ?? 'mysql',
                $config['host'] ?? 'localhost',
                $config['port'] ?? 3306,
                $config['database'] ?? 'u456035299_hospital',
                $config['charset'] ?? 'utf8mb4'
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $config['username'] ?? 'u456035299_hospital',
                    $config['password'] ?? '',
                    $config['options'] ?? []
                );

                // Guarantee Asia/Kolkata +05:30 and utf8mb4 on connection
                self::$connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+05:30'");
            } catch (PDOException $e) {
                // Log full details securely outside web root without exposing credentials
                Logger::emergency('Database connection failed: ' . $e->getMessage(), [
                    'host' => $config['host'] ?? 'localhost',
                    'database' => $config['database'] ?? 'u456035299_hospital',
                    'code' => $e->getCode(),
                ]);

                // Mask details from end user unless debug is requested
                if (App::getInstance()->getConfig('app.debug', false) || (isset($_GET['debug']) && $_GET['debug'] === '1')) {
                    throw new \RuntimeException('Database Connection Error: ' . $e->getMessage(), (int)$e->getCode(), $e);
                } else {
                    throw new \RuntimeException('Database Connection Failed: ' . $e->getMessage() . '. Please verify database credentials in Hostinger or run /install.php to reconfigure.', 500);
                }
            }
        }

        return self::$connection;
    }

    /**
     * Check if database connection is operational (for health checks)
     */
    public static function checkConnection(): array
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query('SELECT 1');
            $status = $stmt !== false && $stmt->fetchColumn() == 1;

            return [
                'connected' => $status,
                'message' => $status ? 'Database operational' : 'Database query test failed',
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'message' => 'Database connection unavailable',
            ];
        }
    }

    /**
     * Execute a prepared SELECT query and return all matching rows
     */
    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alias for select() returning all matching rows
     */
    public static function selectAll(string $sql, array $params = []): array
    {
        return self::select($sql, $params);
    }

    /**
     * Execute a prepared SELECT query and return the first matching row
     */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = self::execute($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Execute a prepared SELECT query and return a single scalar value from the first column of the first row
     */
    public static function selectValue(string $sql, array $params = []): mixed
    {
        $stmt = self::execute($sql, $params);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : null;
    }

    /**
     * Execute an INSERT query and return the last inserted ID
     */
    public static function insert(string $sql, array $params = []): string
    {
        self::execute($sql, $params);
        return self::getConnection()->lastInsertId();
    }

    /**
     * Execute an UPDATE/DELETE query and return the affected rows count
     */
    public static function executeStatement(string $sql, array $params = []): int
    {
        $stmt = self::execute($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Low-level prepared execution wrapper with secure error handling
     */
    public static function execute(string $sql, array $params = []): \PDOStatement
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::error('Database Query Failure', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $sql,
                // Never log raw parameters if they might contain passwords
                'params_keys' => array_keys($params),
            ]);

            if (App::getInstance()->getConfig('app.debug', false)) {
                throw new \RuntimeException("Database Query Error [{$e->getCode()}]: {$e->getMessage()}", (int)$e->getCode(), $e);
            } else {
                throw new \RuntimeException('A database error occurred while processing your request.', 500);
            }
        }
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit active transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback active transaction
     */
    public static function rollBack(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }
}
