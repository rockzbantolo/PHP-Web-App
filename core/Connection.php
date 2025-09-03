<?php
namespace Mws\Core;

class Connection
{
    private static $pool = [];
    private static $activeConnections = 0;
    private static $maxConnections = 50; // Adjust based on your MySQL max_connections
    private static $config = null;

    /**
     * Initialize database configuration once
     */
    private static function initConfig(): void
    {
        if (self::$config === null) {
            self::$config = [
                'host'     => getenv('DB_HOST') ?: 'localhost',
                'username' => getenv('DB_USER') ?: 'root',
                'password' => getenv('DB_PASS') ?: '',
                'database' => getenv('DB_NAME') ?: 'test',
                'port'     => getenv('DB_PORT') ?: 3306,
            ];
        }
    }

    /**
     * Get a connection from the pool (Singleton with pooling)
     *
     * @return \mysqli
     * @throws \Exception
     */
    public static function getInstance(): \mysqli
    {
        self::initConfig();

        // Try to get an available connection from pool
        $connection = self::getPooledConnection();
        if ($connection) {
            return $connection;
        }

        // Create new connection if pool is not full
        if (self::$activeConnections < self::$maxConnections) {
            return self::createNewConnection();
        }

        // Pool is full, wait for available connection
        $timeout = time() + 5; // Wait max 5 seconds
        while (time() < $timeout) {
            $connection = self::getPooledConnection();
            if ($connection) {
                return $connection;
            }
            usleep(100000); // Wait 100ms
        }

        throw new \Exception("Database connection pool exhausted");
    }

    /**
     * Get a connection from the pool
     */
    private static function getPooledConnection(): ?\mysqli
    {
        foreach (self::$pool as $key => $poolItem) {
            if (!$poolItem['inUse'] && $poolItem['connection']->ping()) {
                self::$pool[$key]['inUse'] = true;
                self::$pool[$key]['lastUsed'] = time();
                return $poolItem['connection'];
            }
        }
        return null;
    }

    /**
     * Create a new optimized connection
     */
    private static function createNewConnection(): \mysqli
    {
        $config = self::$config;

        // Create connection with optimized settings
        $conn = new \mysqli(
            $config['host'],
            $config['username'], 
            $config['password'],
            $config['database'],
            $config['port']
        );

        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            throw new \Exception("Database connection failed: " . $conn->connect_error);
        }

        // OPTIMIZATION: Set optimal MySQL settings
        $conn->set_charset("utf8mb4");
        
        // Performance optimizations
        $conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        $conn->query("SET SESSION innodb_lock_wait_timeout = 5");
        $conn->query("SET SESSION wait_timeout = 300");
        $conn->query("SET SESSION interactive_timeout = 300");
        
        // Add to pool
        $poolKey = uniqid('conn_', true);
        self::$pool[$poolKey] = [
            'connection' => $conn,
            'inUse' => true,
            'created' => time(),
            'lastUsed' => time(),
            'key' => $poolKey
        ];

        self::$activeConnections++;
        
        return $conn;
    }

    /**
     * Release a connection back to the pool
     */
    public static function releaseConnection(\mysqli $conn): void
    {
        foreach (self::$pool as $key => $poolItem) {
            if ($poolItem['connection'] === $conn) {
                self::$pool[$key]['inUse'] = false;
                self::$pool[$key]['lastUsed'] = time();
                break;
            }
        }
    }

    /**
     * Clean up old/unused connections
     */
    public static function cleanupPool(): void
    {
        $now = time();
        $maxIdleTime = 300; // 5 minutes

        foreach (self::$pool as $key => $poolItem) {
            // Remove connections idle for too long
            if (!$poolItem['inUse'] && 
                ($now - $poolItem['lastUsed']) > $maxIdleTime) {
                
                $poolItem['connection']->close();
                unset(self::$pool[$key]);
                self::$activeConnections--;
            }
            // Remove broken connections
            elseif (!$poolItem['connection']->ping()) {
                $poolItem['connection']->close();
                unset(self::$pool[$key]);
                self::$activeConnections--;
            }
        }
    }

    /**
     * Optimized transaction methods with proper error handling
     */
    public static function beginTransaction(): bool
    {
        try {
            $conn = self::getInstance();
            $result = $conn->begin_transaction();
            
            if (!$result) {
                error_log("Failed to begin transaction: " . $conn->error);
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("Transaction begin error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function commit(): bool
    {
        try {
            $conn = self::getInstance();
            $result = $conn->commit();
            
            if (!$result) {
                error_log("Failed to commit transaction: " . $conn->error);
            }
            
            // Release connection back to pool after commit
            self::releaseConnection($conn);
            
            return $result;
        } catch (\Exception $e) {
            error_log("Transaction commit error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function rollback(): bool
    {
        try {
            $conn = self::getInstance();
            $result = $conn->rollback();
            
            if (!$result) {
                error_log("Failed to rollback transaction: " . $conn->error);
            }
            
            // Release connection back to pool after rollback
            self::releaseConnection($conn);
            
            return $result;
        } catch (\Exception $e) {
            error_log("Transaction rollback error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pool statistics (useful for monitoring)
     */
    public static function getPoolStats(): array
    {
        $inUse = 0;
        $available = 0;
        
        foreach (self::$pool as $poolItem) {
            if ($poolItem['inUse']) {
                $inUse++;
            } else {
                $available++;
            }
        }
        
        return [
            'total_connections' => self::$activeConnections,
            'in_use' => $inUse,
            'available' => $available,
            'max_connections' => self::$maxConnections
        ];
    }

    /**
     * Graceful shutdown - close all connections
     */
    public static function shutdown(): void
    {
        foreach (self::$pool as $poolItem) {
            if ($poolItem['connection']->ping()) {
                $poolItem['connection']->close();
            }
        }
        
        self::$pool = [];
        self::$activeConnections = 0;
    }
}