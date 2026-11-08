<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;
    
    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        
        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }
    
    public function commit(): void
    {
        $this->connection->commit();
    }
    
    public function rollback(): void
    {
        if ($this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
    }
    
    public function getLastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
    
    private function __clone() {}
}