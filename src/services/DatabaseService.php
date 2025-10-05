<?php

/**
 * Database Service
 * Manages database connections and provides query helpers
 */

class DatabaseService
{
    private static $pdo = null;
    private static $config = null;

    /**
     * Get database connection
     */
    public static function getConnection()
    {
        if (self::$pdo === null) {
            self::connect();
        }
        return self::$pdo;
    }

    /**
     * Establish database connection
     */
    private static function connect()
    {
        try {
            $config = self::getConfig();

            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";

            self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    /**
     * Get database configuration
     */
    private static function getConfig()
    {
        if (self::$config === null) {
            self::$config = ConfigService::get('database', [
                'host' => 'localhost',
                'dbname' => 'rentfinder_sl',
                'username' => 'root',
                'password' => '',
                'port' => 3306,
                'charset' => 'utf8mb4'
            ]);
        }
        return self::$config;
    }

    /**
     * Execute a query
     */
    public static function query($sql, $params = [])
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch single row
     */
    public static function fetch($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert data and return last insert ID
     */
    public static function insert($table, $data)
    {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, $data);

        return self::getConnection()->lastInsertId();
    }

    /**
     * Update data
     */
    public static function update($table, $data, $where, $whereParams = [])
    {
        $setClause = [];
        foreach (array_keys($data) as $key) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);

        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete data
     */
    public static function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction()
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit()
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback()
    {
        return self::getConnection()->rollback();
    }

    /**
     * Check if table exists
     */
    public static function tableExists($table)
    {
        $sql = "SHOW TABLES LIKE :table";
        $result = self::fetch($sql, ['table' => $table]);
        return !empty($result);
    }

    /**
     * Get table columns
     */
    public static function getTableColumns($table)
    {
        $sql = "DESCRIBE {$table}";
        return self::fetchAll($sql);
    }
}
