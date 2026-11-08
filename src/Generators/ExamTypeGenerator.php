<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class ExamTypeGenerator
{
    private PDO $db;
    private array $examTypes = [];
    
    private const EXAM_TYPE_DATA = [
        ['name' => 'Unit Test', 'category' => 'unit_test', 'weightage' => 10.00],
        ['name' => 'Monthly Test', 'category' => 'unit_test', 'weightage' => 15.00],
        ['name' => 'Midterm Exam', 'category' => 'mid_term', 'weightage' => 25.00],
        ['name' => 'Final Exam', 'category' => 'final', 'weightage' => 30.00],
        ['name' => 'Annual Exam', 'category' => 'annual', 'weightage' => 100.00],
        ['name' => 'Practical Exam', 'category' => 'practical', 'weightage' => 20.00],
        ['name' => 'Oral Exam', 'category' => 'oral', 'weightage' => 10.00],
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO exam_types (name, exam_category, weightage_percentage, is_active)
            VALUES (?, ?, ?, 1)
        ");
        
        foreach (self::EXAM_TYPE_DATA as $data) {
            $stmt->execute([
                $data['name'],
                $data['category'],
                $data['weightage']
            ]);
            
            $this->examTypes[$data['category']] = (int)$this->db->lastInsertId();
        }
        
        $this->db->commit();
        
        return $this->examTypes;
    }
    
    public function getExamTypes(): array
    {
        return $this->examTypes;
    }
}