<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class SectionGenerator
{
    private PDO $db;
    private array $sections = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $classIds): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO sections (class_id, section_name, section_code, capacity, room_number, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        $sectionNames = ['A', 'B', 'C', 'D'];
        
        foreach ($classIds as $branchId => $grades) {
            foreach ($grades as $grade => $classId) {
                $numSections = ($grade <= 6) ? 4 : (($grade <= 10) ? 3 : 2);
                
                for ($i = 0; $i < $numSections; $i++) {
                    $sectionName = $sectionNames[$i];
                    $sectionCode = "SEC{$sectionName}CL{$classId}";
                    $roomNumber = "R{$grade}0{$i}";
                    
                    $stmt->execute([
                        $classId,
                        $sectionName,
                        $sectionCode,
                        40,
                        $roomNumber
                    ]);
                    
                    $sectionId = (int)$this->db->lastInsertId();
                    $this->sections[$classId][$i] = $sectionId;
                }
            }
        }
        
        $this->db->commit();
        
        return $this->sections;
    }
    
    public function getSections(): array
    {
        return $this->sections;
    }
}