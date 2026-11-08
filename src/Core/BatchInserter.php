<?php

namespace App\Core;

use PDO;

class BatchInserter
{
    private PDO $db;
    private int $batchSize;
    private array $buffer = [];
    private array $insertedIds = [];
    
    public function __construct(PDO $db, int $batchSize = 1000)
    {
        $this->db = $db;
        $this->batchSize = $batchSize;
    }
    
    public function addRecord(string $table, array $data): void
    {
        $this->buffer[$table][] = $data;
        
        if (count($this->buffer[$table]) >= $this->batchSize) {
            $this->flushTable($table);
        }
    }
    
    public function flushTable(string $table): void
    {
        if (empty($this->buffer[$table])) {
            return;
        }
        
        $records = $this->buffer[$table];
        $columns = array_keys($records[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($records), $placeholders));
        
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES %s',
            $table,
            implode('`, `', $columns),
            $allPlaceholders
        );
        
        $values = [];
        foreach ($records as $record) {
            foreach ($columns as $column) {
                $values[] = $record[$column];
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        
        $firstId = $this->db->lastInsertId();
        $count = count($records);
        for ($i = 0; $i < $count; $i++) {
            $this->insertedIds[] = (int)$firstId + $i;
        }
        
        $this->buffer[$table] = [];
    }
    
    public function flushAll(): void
    {
        foreach (array_keys($this->buffer) as $table) {
            $this->flushTable($table);
        }
    }
    
    public function getInsertedIds(): array
    {
        return $this->insertedIds;
    }
    
    public function clearInsertedIds(): void
    {
        $this->insertedIds = [];
    }
}