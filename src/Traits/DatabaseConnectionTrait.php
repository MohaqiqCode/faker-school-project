<?php

namespace App\Traits;

use App\Core\Database;
use PDO;
use PDOException;

trait DatabaseConnectionTrait
{
    private int $queryCount = 0;
    private int $reconnectThreshold = 1000; // Reconnect every 1000 queries
    
    protected function checkConnection(PDO &$db): void
    {
        $this->queryCount++;
        
        try {
            // Check connection every N queries or when explicitly needed
            if ($this->queryCount % 100 === 0) {
                $db->query('SELECT 1');
            }
        } catch (PDOException $e) {
            // Connection lost, force reconnect
            echo "  Reconnecting to database...\n";
            $db = Database::getInstance()->getConnection(true);
            $this->queryCount = 0;
        }
        
        // Reconnect periodically to prevent timeout
        if ($this->queryCount >= $this->reconnectThreshold) {
            echo "  Periodic reconnection...\n";
            $db = Database::getInstance()->getConnection(true);
            $this->queryCount = 0;
        }
    }
    
    protected function executeWithRetry(PDO &$db, callable $operation, int $maxRetries = 3): mixed
    {
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                $this->checkConnection($db);
                return $operation();
            } catch (PDOException $e) {
                $retryCount++;
                if ($retryCount >= $maxRetries) {
                    throw $e;
                }
                echo "  Query failed, retrying ({$retryCount}/{$maxRetries})...\n";
                $db = Database::getInstance()->getConnection(true);
                sleep(1);
            }
        }
    }
}