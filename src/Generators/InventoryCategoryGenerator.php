<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class InventoryCategoryGenerator
{
    private PDO $db;
    private array $categories = [];
    
    private const CATEGORY_NAMES = [
        'Furniture', 'Electronics', 'Stationery', 'Laboratory Equipment',
        'Sports Equipment', 'Cleaning Supplies', 'Computer Hardware',
        'Office Equipment', 'Teaching Aids', 'Safety Equipment'
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
            INSERT INTO inventory_categories (branch_id, category_name, description)
            VALUES (?, ?, ?)
        ");
        
        foreach ($branchIds as $branchId) {
            foreach (self::CATEGORY_NAMES as $categoryName) {
                $stmt->execute([
                    $branchId,
                    $categoryName,
                    "{$categoryName} for school operations"
                ]);
                
                $catId = (int)$this->db->lastInsertId();
                $this->categories[$branchId][] = $catId;
            }
        }
        
        $this->db->commit();
        
        return $this->categories;
    }
    
    public function getCategories(): array
    {
        return $this->categories;
    }
}