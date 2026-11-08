<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class StudentParentGenerator
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function linkStudentToParent(int $studentId, int $parentId, string $relationship = 'parent'): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO student_parent (student_id, parent_id, relationship, is_emergency_contact, can_pickup)
            VALUES (?, ?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE relationship = VALUES(relationship)
        ");
        
        $stmt->execute([$studentId, $parentId, $relationship]);
    }
    
    public function linkMultipleParents(int $studentId, array $parentIds): void
    {
        foreach ($parentIds as $index => $parentId) {
            $relationship = $index === 0 ? 'father' : 'mother';
            $this->linkStudentToParent($studentId, $parentId, $relationship);
        }
    }
}