<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class FeeCategoryGenerator
{
    private PDO $db;
    private array $feeCategories = [];
    
    private const FEE_CATEGORIES = [
        ['name' => 'Tuition Fee', 'frequency' => 'monthly', 'is_recurring' => true],
        ['name' => 'Admission Fee', 'frequency' => 'one_time', 'is_recurring' => false],
        ['name' => 'Library Fee', 'frequency' => 'yearly', 'is_recurring' => true],
        ['name' => 'Computer Lab Fee', 'frequency' => 'yearly', 'is_recurring' => true],
        ['name' => 'Sports Fee', 'frequency' => 'yearly', 'is_recurring' => true],
        ['name' => 'Examination Fee', 'frequency' => 'half_yearly', 'is_recurring' => true],
        ['name' => 'Transport Fee', 'frequency' => 'monthly', 'is_recurring' => true],
        ['name' => 'Hostel Fee', 'frequency' => 'monthly', 'is_recurring' => true],
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO fee_categories (branch_id, category_name, description, is_recurring, frequency)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($branchIds as $branchId) {
            foreach (self::FEE_CATEGORIES as $category) {
                $stmt->execute([
                    $branchId,
                    $category['name'],
                    "{$category['name']} for students",
                    $category['is_recurring'] ? 1 : 0,
                    $category['frequency']
                ]);
                
                $catId = (int)$this->db->lastInsertId();
                $this->feeCategories[$branchId][] = $catId;
            }
        }
        
        $this->db->commit();
        
        return $this->feeCategories;
    }
    
    public function getFeeCategories(): array
    {
        return $this->feeCategories;
    }
}