<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class InventoryItemGenerator
{
    private PDO $db;
    
    private const ITEMS_BY_CATEGORY = [
        'Furniture' => [
            ['name' => 'Student Desk', 'unit' => 'piece', 'price' => 2500],
            ['name' => 'Teacher Desk', 'unit' => 'piece', 'price' => 5000],
            ['name' => 'Classroom Chair', 'unit' => 'piece', 'price' => 1500],
            ['name' => 'Office Chair', 'unit' => 'piece', 'price' => 3000],
            ['name' => 'Bookshelf', 'unit' => 'piece', 'price' => 4000],
            ['name' => 'Whiteboard', 'unit' => 'piece', 'price' => 3500],
            ['name' => 'Cupboard', 'unit' => 'piece', 'price' => 8000],
        ],
        'Electronics' => [
            ['name' => 'Projector', 'unit' => 'piece', 'price' => 45000],
            ['name' => 'Printer', 'unit' => 'piece', 'price' => 25000],
            ['name' => 'Scanner', 'unit' => 'piece', 'price' => 15000],
            ['name' => 'Photocopier', 'unit' => 'piece', 'price' => 50000],
        ],
        'Computer Hardware' => [
            ['name' => 'Desktop Computer', 'unit' => 'piece', 'price' => 30000],
            ['name' => 'Laptop', 'unit' => 'piece', 'price' => 45000],
            ['name' => 'Network Switch', 'unit' => 'piece', 'price' => 8000],
            ['name' => 'Router', 'unit' => 'piece', 'price' => 5000],
            ['name' => 'Server', 'unit' => 'piece', 'price' => 150000],
            ['name' => 'UPS', 'unit' => 'piece', 'price' => 10000],
        ],
        'Laboratory Equipment' => [
            ['name' => 'Microscope', 'unit' => 'piece', 'price' => 20000],
            ['name' => 'Test Tubes Set', 'unit' => 'set', 'price' => 5000],
            ['name' => 'Bunsen Burner', 'unit' => 'piece', 'price' => 3000],
            ['name' => 'Lab Glassware Set', 'unit' => 'set', 'price' => 12000],
        ],
        'Sports Equipment' => [
            ['name' => 'Football', 'unit' => 'piece', 'price' => 1500],
            ['name' => 'Volleyball', 'unit' => 'piece', 'price' => 1200],
            ['name' => 'Cricket Bat', 'unit' => 'piece', 'price' => 2500],
            ['name' => 'Sports Jersey', 'unit' => 'piece', 'price' => 800],
        ],
        'Stationery' => [
            ['name' => 'A4 Paper Ream', 'unit' => 'ream', 'price' => 300],
            ['name' => 'Pen Box', 'unit' => 'box', 'price' => 500],
            ['name' => 'Notebook', 'unit' => 'piece', 'price' => 50],
            ['name' => 'Marker Set', 'unit' => 'set', 'price' => 400],
        ],
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $categories, array $branchIds): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO inventory_items (category_id, branch_id, item_code, item_name, description, unit, quantity_in_stock, reorder_level, unit_price, supplier_name, location, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        $itemCount = 0;
        $batchSize = 500;
        
        $suppliers = [
            'Kabul Office Supplies', 'Afghan Tech Solutions', 'Educational Equipment Co',
            'School Furniture Ltd', 'Laboratory Supplies Inc', 'Sports Gear Afghanistan'
        ];
        
        foreach ($branchIds as $branchId) {
            if (!isset($categories[$branchId])) continue;
            
            $catIndex = 0;
            foreach ($categories[$branchId] as $categoryId) {
                $categoryName = array_keys(self::ITEMS_BY_CATEGORY)[$catIndex % count(self::ITEMS_BY_CATEGORY)];
                
                if (isset(self::ITEMS_BY_CATEGORY[$categoryName])) {
                    foreach (self::ITEMS_BY_CATEGORY[$categoryName] as $item) {
                        $itemCode = 'ITM' . sprintf('%08d', $itemCount + 1);
                        
                        $stmt->execute([
                            $categoryId,
                            $branchId,
                            $itemCode,
                            $item['name'],
                            "{$item['name']} for school use",
                            $item['unit'],
                            rand(10, 100),
                            rand(5, 20),
                            $item['price'],
                            $suppliers[array_rand($suppliers)],
                            'Room ' . rand(1, 20) . ', Shelf ' . rand(1, 5)
                        ]);
                        
                        $itemCount++;
                        
                        if ($itemCount % $batchSize === 0) {
                            $this->db->commit();
                            $this->db->beginTransaction();
                            echo "  Generated {$itemCount} inventory items...\n";
                        }
                    }
                }
                
                $catIndex++;
            }
        }
        
        $this->db->commit();
        echo "Total inventory items generated: {$itemCount}\n";
    }
}